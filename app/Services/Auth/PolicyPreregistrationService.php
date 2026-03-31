<?php

namespace App\Services\Auth;

use App\Models\Plan;
use App\Models\Policy;
use App\Models\PolicyPreregistration;
use App\Models\User;
use App\Models\WhatsAppSetting;
use App\Services\Policies\GroupPolicyCapacityService;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use App\Services\WhatsApp\WhatsAppDestinationResolver;
use App\Services\WhatsApp\WhatsAppTemplateParameterResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PolicyPreregistrationService
{
    public const STATUS_ACTIVE = 'active';

    public const STATUS_USED = 'used';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_INVALID = 'invalid';

    public function __construct(
        private readonly WhatsAppCloudApiService $whatsAppService,
        private readonly WhatsAppDestinationResolver $destinationResolver,
        private readonly GroupPolicyCapacityService $groupPolicyCapacityService,
        private readonly WhatsAppTemplateParameterResolver $parameterResolver
    ) {}

    /**
     * Create a preregistration invitation and optionally deliver it by WhatsApp.
     *
     * @param  array{company_name?: string|null, company_type?: string|null, company_legal_name?: string|null, company_rfc?: string|null, members?: int|null}  $collectiveData
     * @return array{
     *     preregistration: PolicyPreregistration,
     *     url: string,
     *     expires_at: Carbon,
     *     whatsapp: array{attempted: bool, ok: bool, reason?: string, status?: int}
     * }
     */
    public function createInvitation(
        User $salesUser,
        string $phone,
        ?int $planId,
        ?int $parentPolicyId = null,
        string $preregistrationType = PolicyPreregistration::TYPE_INDIVIDUAL_POLICY,
        array $collectiveData = [],
        bool $deliverWhatsApp = true
    ): array {
        $normalizedPhone = $this->normalizePhone($phone);
        $this->assertPhoneAvailable($normalizedPhone);
        [$plan, $parentPolicy, $token] = $this->prepareInvitationCreation(
            salesUser: $salesUser,
            phone: $normalizedPhone,
            planId: $planId,
            parentPolicyId: $parentPolicyId,
            preregistrationType: $preregistrationType,
            collectiveData: $collectiveData,
        );

        return $this->buildInvitationResponse(
            token: $token,
            actor: $salesUser,
            plan: $plan,
            parentPolicy: $parentPolicy,
            deliverWhatsApp: $deliverWhatsApp,
            logEvent: 'WHATSAPP_POLICY_PREREGISTRATION'
        );
    }

    /**
     * Update a preregistration and rotate its token/link.
     *
     * @param  array{company_name?: string|null, company_type?: string|null, company_legal_name?: string|null, company_rfc?: string|null, members?: int|null}  $collectiveData
     * @return array{
     *     preregistration: PolicyPreregistration,
     *     url: string,
     *     expires_at: Carbon,
     *     whatsapp: array{attempted: bool, ok: bool, reason?: string, status?: int}
     * }
     */
    public function updateInvitation(
        PolicyPreregistration $preregistration,
        User $salesUser,
        string $phone,
        ?int $planId,
        ?int $parentPolicyId = null,
        string $preregistrationType = PolicyPreregistration::TYPE_INDIVIDUAL_POLICY,
        array $collectiveData = [],
        bool $deliverWhatsApp = true
    ): array {
        $this->assertCanManage($preregistration, 'editar');

        $normalizedPhone = $this->normalizePhone($phone);
        $this->assertPhoneAvailable($normalizedPhone, $preregistration->id);
        [$plan, $parentPolicy, $token] = $this->prepareInvitationUpdate(
            preregistration: $preregistration,
            salesUser: $salesUser,
            phone: $normalizedPhone,
            planId: $planId,
            parentPolicyId: $parentPolicyId,
            preregistrationType: $preregistrationType,
            collectiveData: $collectiveData,
        );

        return $this->buildInvitationResponse(
            token: $token,
            actor: $salesUser,
            plan: $plan,
            parentPolicy: $parentPolicy,
            deliverWhatsApp: $deliverWhatsApp,
            logEvent: 'WHATSAPP_POLICY_PREREGISTRATION_UPDATED'
        );
    }

    /**
     * Cancel an existing preregistration.
     */
    public function cancelInvitation(PolicyPreregistration $preregistration, User $actor): void
    {
        $this->assertCanManage($preregistration, 'cancelar');

        $preregistration->forceFill([
            'cancelled_by' => $actor->id,
            'cancelled_at' => now(),
        ])->save();
    }

    /**
     * Resolve an active preregistration token.
     */
    public function resolveActiveToken(string $plainTextToken): ?PolicyPreregistration
    {
        $resolved = $this->resolveTokenStatus($plainTextToken);

        return $resolved['status'] === self::STATUS_ACTIVE
            ? $resolved['preregistration']
            : null;
    }

    /**
     * Resolve token and identify current status.
     *
     * @return array{preregistration: PolicyPreregistration|null, status: string}
     */
    public function resolveTokenStatus(string $plainTextToken): array
    {
        $preregistration = PolicyPreregistration::query()
            ->with(['plan', 'parentPolicy', 'salesUser'])
            ->where('token_hash', hash('sha256', $plainTextToken))
            ->first();

        if (! $preregistration) {
            return [
                'preregistration' => null,
                'status' => self::STATUS_INVALID,
            ];
        }

        if ($preregistration->cancelled_at !== null) {
            return [
                'preregistration' => $preregistration,
                'status' => self::STATUS_CANCELLED,
            ];
        }

        if ($preregistration->used_at !== null) {
            return [
                'preregistration' => $preregistration,
                'status' => self::STATUS_USED,
            ];
        }

        if ($preregistration->expires_at->isPast()) {
            return [
                'preregistration' => $preregistration,
                'status' => self::STATUS_EXPIRED,
            ];
        }

        return [
            'preregistration' => $preregistration,
            'status' => self::STATUS_ACTIVE,
        ];
    }

    /**
     * Mark the preregistration as used after successful policy creation.
     */
    public function consumeToken(PolicyPreregistration $preregistration): void
    {
        if ($preregistration->used_at !== null) {
            return;
        }

        $preregistration->forceFill([
            'used_at' => now(),
        ])->save();
    }

    /**
     * Create a new preregistration token.
     *
     * @return array{preregistration: PolicyPreregistration, plain_text_token: string, expires_at: Carbon}
     */
    private function createToken(
        User $salesUser,
        ?Plan $plan,
        ?Policy $parentPolicy,
        string $phone,
        string $preregistrationType,
        array $collectiveData = []
    ): array
    {
        $now = now();
        $expiresAt = $now->copy()->addMinutes((int) config('auth.policy_preregistration.ttl', 10080));
        $plainTextToken = Str::random(64);

        $preregistration = DB::transaction(function () use ($salesUser, $plan, $parentPolicy, $phone, $expiresAt, $plainTextToken, $preregistrationType, $collectiveData) {
            return PolicyPreregistration::query()->create([
                'sales_user_id' => $salesUser->id,
                'plan_id' => $plan?->id,
                'parent_policy_id' => $parentPolicy?->id,
                'preregistration_type' => $preregistrationType,
                'company_name' => $collectiveData['company_name'] ?? null,
                'company_type' => $collectiveData['company_type'] ?? null,
                'company_legal_name' => $collectiveData['company_legal_name'] ?? null,
                'company_rfc' => $collectiveData['company_rfc'] ?? null,
                'members' => $collectiveData['members'] ?? null,
                'phone' => $phone,
                'token_hash' => hash('sha256', $plainTextToken),
                'expires_at' => $expiresAt,
            ]);
        });

        return [
            'preregistration' => $preregistration,
            'plain_text_token' => $plainTextToken,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Rotate the token and update data for an existing preregistration.
     *
     * @return array{preregistration: PolicyPreregistration, plain_text_token: string, expires_at: Carbon}
     */
    private function refreshToken(
        PolicyPreregistration $preregistration,
        User $salesUser,
        ?Plan $plan,
        ?Policy $parentPolicy,
        string $phone,
        string $preregistrationType,
        array $collectiveData = []
    ): array {
        $expiresAt = now()->addMinutes((int) config('auth.policy_preregistration.ttl', 10080));
        $plainTextToken = Str::random(64);

        $preregistration->forceFill([
            'sales_user_id' => $salesUser->id,
            'plan_id' => $plan?->id,
            'parent_policy_id' => $parentPolicy?->id,
            'preregistration_type' => $preregistrationType,
            'company_name' => $collectiveData['company_name'] ?? null,
            'company_type' => $collectiveData['company_type'] ?? null,
            'company_legal_name' => $collectiveData['company_legal_name'] ?? null,
            'company_rfc' => $collectiveData['company_rfc'] ?? null,
            'members' => $collectiveData['members'] ?? null,
            'phone' => $phone,
            'token_hash' => hash('sha256', $plainTextToken),
            'expires_at' => $expiresAt,
        ])->save();

        return [
            'preregistration' => $preregistration->fresh(['plan', 'parentPolicy', 'salesUser']),
            'plain_text_token' => $plainTextToken,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Attempt to deliver preregistration link via WhatsApp.
     *
     * @return array{attempted: bool, ok: bool, reason?: string, status?: int}
     */
    private function sendWhatsAppTemplate(PolicyPreregistration $preregistration, string $plainTextToken): array
    {
        $preregistration->loadMissing('salesUser', 'plan', 'parentPolicy.user.company');

        $setting = WhatsAppSetting::query()->first();

        if (! $setting || ! filled($setting->access_token) || ! filled($setting->phone_number_id)) {
            return [
                'attempted' => false,
                'ok' => false,
                'reason' => 'missing_api_credentials',
            ];
        }

        if (! filled($setting->preregistration_template_name)) {
            return [
                'attempted' => false,
                'ok' => false,
                'reason' => 'missing_template_name',
            ];
        }

        $destinations = $this->destinationResolver->resolve($preregistration->phone, '52');

        if ($destinations === []) {
            return [
                'attempted' => false,
                'ok' => false,
                'reason' => 'invalid_phone',
            ];
        }

        $languageCode = $setting->preregistration_language_code ?: ($setting->default_language ?: 'es_MX');
        $lastResponse = null;
        $parameterContext = [
            'preregistration' => $preregistration,
            'promoter' => $preregistration->salesUser,
            'plan' => $preregistration->plan,
            'parent_policy' => $preregistration->parentPolicy,
            'preregistration_token' => $plainTextToken,
        ];
        $bodyParameters = $this->parameterResolver->resolve(
            $setting->preregistration_body_parameters,
            WhatsAppTemplateParameterResolver::PREREGISTRATION_BODY,
            $parameterContext
        );
        $buttonParameters = $this->parameterResolver->resolve(
            $setting->preregistration_button_parameters,
            WhatsAppTemplateParameterResolver::PREREGISTRATION_BUTTON,
            $parameterContext
        );

        foreach ($destinations as $destination) {
            $lastResponse = $this->whatsAppService->sendTemplateMessage(
                setting: $setting,
                to: $destination,
                templateName: $setting->preregistration_template_name,
                languageCode: $languageCode,
                parameters: $bodyParameters,
                buttonUrlParameters: $buttonParameters
            );

            if ($lastResponse['ok']) {
                return [
                    'attempted' => true,
                    'ok' => true,
                    'status' => $lastResponse['status'],
                    'to' => $destination,
                ];
            }
        }

        return [
            'attempted' => true,
            'ok' => false,
            'status' => $lastResponse['status'] ?? null,
            'reason' => 'all_destinations_failed',
            'tried_to' => $destinations,
        ];
    }

    /**
     * Validate that the selected plan is active.
     */
    private function resolvePlan(?int $planId): Plan
    {
        if (! $planId) {
            throw new InvalidArgumentException('La membresía seleccionada no esta disponible para preregistro.');
        }

        $plan = Plan::query()
            ->whereKey($planId)
            ->where('status', 'Active')
            ->first();

        if (! $plan) {
            throw new InvalidArgumentException('La membresía seleccionada no esta disponible para preregistro.');
        }

        return $plan;
    }

    /**
     * Validate that the selected plan is active for collective owner preregistration.
     */
    private function resolveGroupOwnerPlan(?int $planId): Plan
    {
        if (! $planId) {
            throw new InvalidArgumentException('La cobertura seleccionada no esta disponible para preregistro.');
        }

        $plan = Plan::query()
            ->whereKey($planId)
            ->where('status', 'Active')
            ->first();

        if (! $plan) {
            throw new InvalidArgumentException('La cobertura seleccionada no esta disponible para preregistro.');
        }

        return $plan;
    }

    /**
     * Validate and normalize collective owner data captured by the sales user.
     *
     * @param  array{company_name?: string|null, company_type?: string|null, company_legal_name?: string|null, company_rfc?: string|null, members?: int|null}  $collectiveData
     * @return array{company_name: string, company_type: string, company_legal_name: string, company_rfc: string, members: int}
     */
    private function normalizeCollectiveData(array $collectiveData): array
    {
        $companyName = trim((string) ($collectiveData['company_name'] ?? ''));
        $companyType = trim((string) ($collectiveData['company_type'] ?? ''));
        $companyLegalName = trim((string) ($collectiveData['company_legal_name'] ?? ''));
        $companyRfc = strtoupper(trim((string) ($collectiveData['company_rfc'] ?? '')));
        $members = (int) ($collectiveData['members'] ?? 0);

        if ($companyName === '') {
            throw new InvalidArgumentException('El nombre del colectivo es obligatorio.');
        }

        if (! in_array($companyType, ['PF', 'PM', 'PFA'], true)) {
            throw new InvalidArgumentException('Selecciona el tipo de persona del colectivo.');
        }

        if ($companyLegalName === '') {
            throw new InvalidArgumentException('La razon social del colectivo es obligatoria.');
        }

        if (! preg_match('/^[A-Z&Ñ]{3,4}[0-9]{6}[A-Z0-9]{3}$/', $companyRfc)) {
            throw new InvalidArgumentException('El RFC del colectivo no es valido.');
        }

        if ($members < 1 || $members > 99) {
            throw new InvalidArgumentException('La cantidad de miembros del colectivo no es valida.');
        }

        return [
            'company_name' => $companyName,
            'company_type' => $companyType,
            'company_legal_name' => $companyLegalName,
            'company_rfc' => $companyRfc,
            'members' => $members,
        ];
    }

    /**
     * Resolve a group-member preregistration target and validate its capacity.
     *
     * @return array{0: Plan, 1: Policy}
     */
    private function resolveGroupMemberTarget(?int $parentPolicyId, ?int $ignorePreregistrationId = null): array
    {
        if (! $parentPolicyId) {
            throw new InvalidArgumentException('Selecciona la membresía colectiva a la que pertenece el miembro.');
        }

        $groupPolicy = $this->groupPolicyCapacityService->resolveGroupPolicy($parentPolicyId, true);
        $this->groupPolicyCapacityService->assertHasAvailableSlot($groupPolicy, $ignorePreregistrationId);

        return [$groupPolicy->plan, $groupPolicy];
    }

    /**
     * Validate the selected parent policy when present.
     */
    private function resolveParentPolicy(?int $parentPolicyId): ?Policy
    {
        if (! $parentPolicyId) {
            return null;
        }

        $policy = Policy::query()
            ->whereKey($parentPolicyId)
            ->whereNull('parent_policy_id')
            ->first();

        if (! $policy) {
            throw new InvalidArgumentException('La membresía principal seleccionada no es valida.');
        }

        return $policy;
    }

    /**
     * Prepare individual or collective preregistration creation.
     *
     * @return array{0: Plan|null, 1: Policy|null, 2: array{preregistration: PolicyPreregistration, plain_text_token: string, expires_at: Carbon}}
     */
    private function prepareInvitationCreation(
        User $salesUser,
        string $phone,
        ?int $planId,
        ?int $parentPolicyId,
        string $preregistrationType,
        array $collectiveData = []
    ): array {
        if ($preregistrationType === PolicyPreregistration::TYPE_GROUP_MEMBER) {
            return DB::transaction(function () use ($salesUser, $phone, $parentPolicyId, $preregistrationType) {
                [$plan, $parentPolicy] = $this->resolveGroupMemberTarget($parentPolicyId);

                return [
                    $plan,
                    $parentPolicy,
                    $this->createToken(
                        salesUser: $salesUser,
                        plan: $plan,
                        parentPolicy: $parentPolicy,
                        phone: $phone,
                        preregistrationType: $preregistrationType
                    ),
                ];
            });
        }

        if ($preregistrationType === PolicyPreregistration::TYPE_GROUP_OWNER) {
            $normalizedCollectiveData = $this->normalizeCollectiveData($collectiveData);
            $plan = $this->resolveGroupOwnerPlan($planId);

            return [
                $plan,
                null,
                $this->createToken(
                    salesUser: $salesUser,
                    plan: $plan,
                    parentPolicy: null,
                    phone: $phone,
                    preregistrationType: $preregistrationType,
                    collectiveData: $normalizedCollectiveData
                ),
            ];
        }

        $plan = $this->resolvePlan($planId);
        $parentPolicy = $this->resolveParentPolicy($parentPolicyId);

        return [
            $plan,
            $parentPolicy,
            $this->createToken(
                salesUser: $salesUser,
                plan: $plan,
                parentPolicy: $parentPolicy,
                phone: $phone,
                preregistrationType: $preregistrationType
            ),
        ];
    }

    /**
     * Prepare individual or collective preregistration update.
     *
     * @return array{0: Plan|null, 1: Policy|null, 2: array{preregistration: PolicyPreregistration, plain_text_token: string, expires_at: Carbon}}
     */
    private function prepareInvitationUpdate(
        PolicyPreregistration $preregistration,
        User $salesUser,
        string $phone,
        ?int $planId,
        ?int $parentPolicyId,
        string $preregistrationType,
        array $collectiveData = []
    ): array {
        if ($preregistrationType === PolicyPreregistration::TYPE_GROUP_MEMBER) {
            return DB::transaction(function () use ($preregistration, $salesUser, $phone, $parentPolicyId, $preregistrationType) {
                [$plan, $parentPolicy] = $this->resolveGroupMemberTarget($parentPolicyId, $preregistration->id);

                return [
                    $plan,
                    $parentPolicy,
                    $this->refreshToken(
                        preregistration: $preregistration,
                        salesUser: $salesUser,
                        plan: $plan,
                        parentPolicy: $parentPolicy,
                        phone: $phone,
                        preregistrationType: $preregistrationType
                    ),
                ];
            });
        }

        if ($preregistrationType === PolicyPreregistration::TYPE_GROUP_OWNER) {
            $normalizedCollectiveData = $this->normalizeCollectiveData($collectiveData);
            $plan = $this->resolveGroupOwnerPlan($planId);

            return [
                $plan,
                null,
                $this->refreshToken(
                    preregistration: $preregistration,
                    salesUser: $salesUser,
                    plan: $plan,
                    parentPolicy: null,
                    phone: $phone,
                    preregistrationType: $preregistrationType,
                    collectiveData: $normalizedCollectiveData
                ),
            ];
        }

        $plan = $this->resolvePlan($planId);
        $parentPolicy = $this->resolveParentPolicy($parentPolicyId);

        return [
            $plan,
            $parentPolicy,
            $this->refreshToken(
                preregistration: $preregistration,
                salesUser: $salesUser,
                plan: $plan,
                parentPolicy: $parentPolicy,
                phone: $phone,
                preregistrationType: $preregistrationType
            ),
        ];
    }

    /**
     * Normalize and validate a MX phone.
     */
    private function normalizePhone(string $phone): string
    {
        $normalizedPhone = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($normalizedPhone) !== 10) {
            throw new InvalidArgumentException('El telefono debe contener 10 digitos.');
        }

        return $normalizedPhone;
    }

    /**
     * Ensure there is no registered user for the phone.
     */
    private function assertPhoneAvailable(string $phone, ?int $ignorePreregistrationId = null): void
    {
        if (User::query()->where('phone', $phone)->exists()) {
            throw new InvalidArgumentException('Ya existe un usuario registrado con ese telefono.');
        }

        $query = PolicyPreregistration::query()->where('phone', $phone);

        if ($ignorePreregistrationId !== null) {
            $query->whereKeyNot($ignorePreregistrationId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException('Ya existe un preregistro registrado con ese telefono.');
        }
    }

    /**
     * Ensure the preregistration can still be edited or cancelled.
     */
    private function assertCanManage(PolicyPreregistration $preregistration, string $action): void
    {
        if ($preregistration->cancelled_at !== null) {
            throw new InvalidArgumentException("No es posible {$action} un preregistro cancelado.");
        }

        if ($preregistration->used_at !== null) {
            throw new InvalidArgumentException("No es posible {$action} un preregistro utilizado.");
        }
    }

    /**
     * Build the response payload and optionally deliver WhatsApp.
     *
     * @param  array{preregistration: PolicyPreregistration, plain_text_token: string, expires_at: Carbon}  $token
     * @return array{
     *     preregistration: PolicyPreregistration,
     *     url: string,
     *     expires_at: Carbon,
     *     whatsapp: array{attempted: bool, ok: bool, reason?: string, status?: int}
     * }
     */
    private function buildInvitationResponse(
        array $token,
        User $actor,
        ?Plan $plan,
        ?Policy $parentPolicy,
        bool $deliverWhatsApp,
        string $logEvent
    ): array {
        $url = route('policy.preregistration', ['token' => $token['plain_text_token']]);
        $whatsAppDelivery = $deliverWhatsApp
            ? $this->sendWhatsAppTemplate($token['preregistration'], $token['plain_text_token'])
            : ['attempted' => false, 'ok' => false, 'reason' => 'delivery_skipped'];

        Log::info($logEvent, [
            'policy_preregistration_id' => $token['preregistration']->id,
            'sales_user_id' => $actor->id,
            'phone' => $token['preregistration']->phone,
            'plan_id' => $plan?->id,
            'parent_policy_id' => $parentPolicy?->id,
            'preregistration_type' => $token['preregistration']->preregistration_type,
            'company_name' => $token['preregistration']->company_name,
            'url' => $url,
            'expires_at' => $token['expires_at']->toDateTimeString(),
            'whatsapp' => $whatsAppDelivery,
        ]);

        return [
            'preregistration' => $token['preregistration'],
            'url' => $url,
            'expires_at' => $token['expires_at'],
            'whatsapp' => $whatsAppDelivery,
        ];
    }
}
