<?php

namespace App\Services\Auth;

use App\Models\Plan;
use App\Models\Policy;
use App\Models\PolicyPreregistration;
use App\Models\User;
use App\Models\WhatsAppSetting;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use App\Services\WhatsApp\WhatsAppDestinationResolver;
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

    public const STATUS_INVALID = 'invalid';

    public function __construct(
        private readonly WhatsAppCloudApiService $whatsAppService,
        private readonly WhatsAppDestinationResolver $destinationResolver
    ) {}

    /**
     * Create a preregistration invitation and optionally deliver it by WhatsApp.
     *
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
        int $planId,
        ?int $parentPolicyId = null,
        bool $deliverWhatsApp = true
    ): array {
        $normalizedPhone = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($normalizedPhone) !== 10) {
            throw new InvalidArgumentException('El telefono debe contener 10 digitos.');
        }

        if (User::query()->where('phone', $normalizedPhone)->exists()) {
            throw new InvalidArgumentException('Ya existe un usuario registrado con ese telefono.');
        }

        $plan = $this->resolvePlan($planId);
        $parentPolicy = $this->resolveParentPolicy($parentPolicyId);
        $token = $this->createToken(
            salesUser: $salesUser,
            plan: $plan,
            parentPolicy: $parentPolicy,
            phone: $normalizedPhone
        );
        $url = route('policy.preregistration', ['token' => $token['plain_text_token']]);
        $whatsAppDelivery = $deliverWhatsApp
            ? $this->sendWhatsAppTemplate($token['preregistration'], $token['plain_text_token'])
            : ['attempted' => false, 'ok' => false, 'reason' => 'delivery_skipped'];

        Log::info('WHATSAPP_POLICY_PREREGISTRATION', [
            'policy_preregistration_id' => $token['preregistration']->id,
            'sales_user_id' => $salesUser->id,
            'phone' => $token['preregistration']->phone,
            'plan_id' => $plan->id,
            'parent_policy_id' => $parentPolicy?->id,
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
    private function createToken(User $salesUser, Plan $plan, ?Policy $parentPolicy, string $phone): array
    {
        $now = now();
        $expiresAt = $now->copy()->addMinutes((int) config('auth.policy_preregistration.ttl', 10080));
        $plainTextToken = Str::random(64);

        $preregistration = DB::transaction(function () use ($salesUser, $plan, $parentPolicy, $phone, $expiresAt, $plainTextToken) {
            return PolicyPreregistration::query()->create([
                'sales_user_id' => $salesUser->id,
                'plan_id' => $plan->id,
                'parent_policy_id' => $parentPolicy?->id,
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
     * Attempt to deliver preregistration link via WhatsApp.
     *
     * @return array{attempted: bool, ok: bool, reason?: string, status?: int}
     */
    private function sendWhatsAppTemplate(PolicyPreregistration $preregistration, string $plainTextToken): array
    {
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

        $languageCode = $setting->default_language ?: 'es_MX';
        $lastResponse = null;

        foreach ($destinations as $destination) {
            $lastResponse = $this->whatsAppService->sendTemplateMessage(
                setting: $setting,
                to: $destination,
                templateName: $setting->preregistration_template_name,
                languageCode: $languageCode,
                parameters: [],
                buttonUrlParameters: [$plainTextToken]
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
     * Validate that the selected plan is active and individual.
     */
    private function resolvePlan(int $planId): Plan
    {
        $plan = Plan::query()
            ->whereKey($planId)
            ->where('type', 'Individual')
            ->where('status', 'Active')
            ->first();

        if (! $plan) {
            throw new InvalidArgumentException('La cobertura seleccionada no esta disponible para preregistro.');
        }

        return $plan;
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
            throw new InvalidArgumentException('La poliza principal seleccionada no es valida.');
        }

        return $policy;
    }
}
