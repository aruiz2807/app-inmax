<?php

namespace App\Services\WhatsApp;

use App\Models\Appointment;
use App\Models\Plan;
use App\Models\Policy;
use App\Models\PolicyPreregistration;
use App\Models\User;
use Carbon\CarbonInterface;

class WhatsAppTemplateParameterResolver
{
    public const ACTIVATION_BODY = 'activation_body';
    public const ACTIVATION_BUTTON = 'activation_button';
    public const PIN_RESET_BODY = 'pin_reset_body';
    public const PIN_RESET_BUTTON = 'pin_reset_button';
    public const PREREGISTRATION_BODY = 'preregistration_body';
    public const PREREGISTRATION_BUTTON = 'preregistration_button';
    public const APPOINTMENT_REQUEST_BODY = 'appointment_request_body';
    public const APPOINTMENT_REQUEST_BUTTON = 'appointment_request_button';
    public const APPOINTMENT_COMPLETED_BODY = 'appointment_completed_body';
    public const APPOINTMENT_COMPLETED_BUTTON = 'appointment_completed_button';

    /**
     * Available variables by template section.
     *
     * @var array<string, array<string, string>>
     */
    private const OPTIONS = [
        self::ACTIVATION_BODY => [
            'user_name' => 'Nombre del usuario',
            'user_phone' => 'Telefono del usuario',
            'policy_number' => 'Numero de membresia',
            'policy_type' => 'Tipo de poliza',
            'start_date' => 'Fecha de inicio',
            'sales_user_name' => 'Nombre del promotor',
        ],
        self::ACTIVATION_BUTTON => [
            'pin_token' => 'Token del enlace PIN',
        ],
        self::PIN_RESET_BODY => [
            'user_name' => 'Nombre del usuario',
            'user_phone' => 'Telefono del usuario',
            'policy_number' => 'Numero de membresia',
            'policy_type' => 'Tipo de poliza',
            'sales_user_name' => 'Nombre del promotor',
        ],
        self::PIN_RESET_BUTTON => [
            'pin_token' => 'Token del enlace PIN',
        ],
        self::PREREGISTRATION_BODY => [
            'promoter_name' => 'Nombre del promotor',
            'plan_name' => 'Nombre de la cobertura',
            'parent_policy_number' => 'Numero de poliza principal/colectiva',
            'company_name' => 'Empresa relacionada',
        ],
        self::PREREGISTRATION_BUTTON => [
            'preregistration_token' => 'Token del preregistro',
        ],
        self::APPOINTMENT_REQUEST_BODY => [
            'member_name' => 'Nombre del miembro',
            'appointment_date' => 'Fecha de la cita',
            'appointment_time' => 'Hora de la cita',
            'doctor_name' => 'Nombre del doctor/proveedor',
        ],
        self::APPOINTMENT_REQUEST_BUTTON => [],
        self::APPOINTMENT_COMPLETED_BODY => [
            'member_name' => 'Nombre del miembro',
            'completed_date' => 'Fecha de finalizacion',
            'doctor_name' => 'Nombre del doctor/proveedor',
            'appointment_date' => 'Fecha de la cita',
        ],
        self::APPOINTMENT_COMPLETED_BUTTON => [],
    ];

    /**
     * Legacy defaults used when a mapping was never configured.
     *
     * @var array<string, array<int, string>>
     */
    private const DEFAULTS = [
        self::ACTIVATION_BODY => ['user_name'],
        self::ACTIVATION_BUTTON => ['pin_token'],
        self::PIN_RESET_BODY => ['user_name'],
        self::PIN_RESET_BUTTON => ['pin_token'],
        self::PREREGISTRATION_BODY => ['promoter_name'],
        self::PREREGISTRATION_BUTTON => ['preregistration_token'],
        self::APPOINTMENT_REQUEST_BODY => ['member_name', 'appointment_date', 'appointment_time'],
        self::APPOINTMENT_REQUEST_BUTTON => [],
        self::APPOINTMENT_COMPLETED_BODY => ['member_name', 'completed_date', 'doctor_name'],
        self::APPOINTMENT_COMPLETED_BUTTON => [],
    ];

    /**
     * Return available options for all scopes.
     *
     * @return array<string, array<string, string>>
     */
    public function allOptions(): array
    {
        return self::OPTIONS;
    }

    /**
     * Return available keys for a scope.
     *
     * @return array<int, string>
     */
    public function allowedKeys(string $scope): array
    {
        return array_keys(self::OPTIONS[$scope] ?? []);
    }

    /**
     * Return the default keys for a scope.
     *
     * @return array<int, string>
     */
    public function defaultKeys(string $scope): array
    {
        return self::DEFAULTS[$scope] ?? [];
    }

    /**
     * Return a human-readable hint string for a scope.
     */
    public function hintText(string $scope): string
    {
        return collect(self::OPTIONS[$scope] ?? [])
            ->map(fn (string $label, string $key) => sprintf('%s (%s)', $key, $label))
            ->implode(', ');
    }

    /**
     * Convert configured keys into resolved string values.
     *
     * @param  array<int, string>|string|null  $configuredKeys
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    public function resolve(array|string|null $configuredKeys, string $scope, array $context): array
    {
        $keys = $this->normalizeConfiguredKeys($configuredKeys, $scope);

        return array_map(
            fn (string $key) => $this->stringify($this->resolveValue($key, $context)),
            $keys
        );
    }

    /**
     * Normalize configured keys, falling back only when the mapping is truly unset.
     *
     * @param  array<int, string>|string|null  $configuredKeys
     * @return array<int, string>
     */
    public function normalizeConfiguredKeys(array|string|null $configuredKeys, string $scope): array
    {
        if ($configuredKeys === null) {
            return $this->defaultKeys($scope);
        }

        return $this->extractKeys($configuredKeys);
    }

    /**
     * Return invalid keys for a given scope.
     *
     * @param  array<int, string>|string|null  $configuredKeys
     * @return array<int, string>
     */
    public function invalidKeys(array|string|null $configuredKeys, string $scope): array
    {
        $keys = $this->extractKeys($configuredKeys);

        return array_values(array_diff($keys, $this->allowedKeys($scope)));
    }

    /**
     * Extract ordered keys from textarea lines or arrays.
     *
     * @param  array<int, string>|string|null  $configuredKeys
     * @return array<int, string>
     */
    public function extractKeys(array|string|null $configuredKeys): array
    {
        if ($configuredKeys === null) {
            return [];
        }

        if (is_array($configuredKeys)) {
            return array_values(array_filter(
                array_map(fn (mixed $key) => trim((string) $key), $configuredKeys),
                fn (string $key) => $key !== ''
            ));
        }

        return collect(preg_split('/\R/', $configuredKeys) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Resolve a single configured key from a context map.
     *
     * @param  array<string, mixed>  $context
     */
    private function resolveValue(string $key, array $context): mixed
    {
        $user = $this->resolveUser($context);
        $policy = $this->resolvePolicy($context);
        $salesUser = $this->resolveSalesUser($context, $policy);
        $preregistration = $context['preregistration'] ?? null;
        $plan = $this->resolvePlan($context, $policy, $preregistration);
        $parentPolicy = $this->resolveParentPolicy($context, $policy, $preregistration);
        $appointment = $context['appointment'] ?? null;
        $doctorName = $this->resolveDoctorName($context, $appointment);
        $completedAt = $this->resolveCompletedAt($context, $appointment);

        return match ($key) {
            'user_name', 'member_name' => $user?->name,
            'user_phone' => $user?->phone,
            'policy_number' => $policy?->number,
            'policy_type' => $policy?->type,
            'start_date' => $policy?->start_date?->format('d/m/Y'),
            'sales_user_name', 'promoter_name' => $salesUser?->name,
            'plan_name' => $plan?->name,
            'parent_policy_number' => $parentPolicy?->number,
            'company_name' => $parentPolicy?->user?->company?->name
                ?? $policy?->user?->company?->name
                ?? $user?->company?->name,
            'pin_token' => $context['pin_token'] ?? null,
            'preregistration_token' => $context['preregistration_token'] ?? null,
            'appointment_date' => $appointment?->date?->format('d/m/Y'),
            'appointment_time' => $appointment?->time?->format('h:i A'),
            'doctor_name' => $doctorName,
            'completed_date' => $completedAt?->format('d/m/Y'),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveUser(array $context): ?User
    {
        if (($context['user'] ?? null) instanceof User) {
            return $context['user'];
        }

        if (($context['member'] ?? null) instanceof User) {
            return $context['member'];
        }

        $appointment = $context['appointment'] ?? null;

        return $appointment instanceof Appointment ? $appointment->user : null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolvePolicy(array $context): ?Policy
    {
        if (($context['policy'] ?? null) instanceof Policy) {
            return $context['policy'];
        }

        $user = $this->resolveUser($context);

        return $user?->policy;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveSalesUser(array $context, ?Policy $policy): ?User
    {
        if (($context['sales_user'] ?? null) instanceof User) {
            return $context['sales_user'];
        }

        if (($context['promoter'] ?? null) instanceof User) {
            return $context['promoter'];
        }

        return $policy?->sales_user;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolvePlan(array $context, ?Policy $policy, mixed $preregistration): ?Plan
    {
        if (($context['plan'] ?? null) instanceof Plan) {
            return $context['plan'];
        }

        if ($policy?->relationLoaded('plan') || $policy?->plan) {
            return $policy->plan;
        }

        return $preregistration?->plan;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveParentPolicy(array $context, ?Policy $policy, mixed $preregistration): ?Policy
    {
        if (($context['parent_policy'] ?? null) instanceof Policy) {
            return $context['parent_policy'];
        }

        if ($preregistration instanceof PolicyPreregistration) {
            return $preregistration->parentPolicy;
        }

        return $policy?->parentPolicy;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveDoctorName(array $context, mixed $appointment): ?string
    {
        if (filled($context['doctor_name'] ?? null)) {
            return (string) $context['doctor_name'];
        }

        return $appointment instanceof Appointment
            ? $appointment->doctor?->user?->name
            : null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveCompletedAt(array $context, mixed $appointment): ?CarbonInterface
    {
        if (($context['completed_at'] ?? null) instanceof CarbonInterface) {
            return $context['completed_at'];
        }

        if (! $appointment instanceof Appointment) {
            return null;
        }

        return $appointment->note?->created_at
            ?? $appointment->updated_at
            ?? $appointment->created_at;
    }

    /**
     * Safely cast resolved values to strings for Meta.
     */
    private function stringify(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }
}
