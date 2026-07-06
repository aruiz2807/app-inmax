<?php

namespace App\Services\Policies;

use App\Models\PlanBenefit;
use App\Models\PlanCoverage;
use App\Models\Policy;
use App\Models\PolicyService;
use App\Models\PolicyLegalInformation;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class IndividualPolicyRegistrationService
{
    /**
     * Create an individual or member policy with its user and included services.
     *
     * @param  array{
     *     name: string,
     *     email: string,
     *     phone: string,
     *     birth: string,
     *     curp: string|null,
     *     passport: string,
     *     path: string,
     *     plan_id: int,
     *     sales_user_id: int|null,
     *     parent_policy_id: int|null,
     *     insurance: array<int, string>,
     *     adding_member: bool,
     *     policy_preregistration_id?: int|null
     * }  $payload
     */
    public function create(array $payload): Policy
    {
        return DB::transaction(function () use ($payload) {
            $parentPolicy = $payload['parent_policy_id']
                ? Policy::query()->with('user')->findOrFail($payload['parent_policy_id'])
                : null;

            $user = User::query()->create([
                'name' => $payload['name'],
                'profile' => 'User',
                'email' => $payload['email'],
                'phone' => $payload['phone'],
                'birth_date' => $payload['birth'],
                'curp' => $payload['curp'],
                'passport' => $payload['passport'],
                'company_id' => $payload['adding_member'] ? $parentPolicy?->user?->company_id : null,
                'password' => Hash::make($payload['phone']),
                'profile_photo_path' => $payload['path'],
            ]);

            $policy = Policy::query()->create([
                'user_id' => $user->id,
                'sales_user_id' => $payload['sales_user_id'],
                'plan_id' => $payload['plan_id'],
                'parent_policy_id' => $payload['parent_policy_id'],
                'policy_preregistration_id' => $payload['policy_preregistration_id'] ?? null,
                'number' => $this->buildPolicyNumber(
                    planId: $payload['plan_id'],
                    parentPolicyId: $payload['parent_policy_id']
                ),
                'type' => $payload['adding_member'] ? 'Member' : 'Individual',
                'insurance' => $payload['insurance'],
            ]);

            $legalInfo = PolicyLegalInformation::query()->create([
                'policy_id' => $policy->id,
                'legal_name' => $payload['legal_name'],
                'legal_address' => $payload['legal_address'],
                'legal_relationship_id' => $payload['legal_relationship_id'],
                'cfdi_rfc' => $payload['cfdi_rfc'],
                'cfdi_name' => $payload['cfdi_name'],
                'cfdi_postal_code' => $payload['cfdi_postal_code'],
                'cfdi_regime_id' => $payload['cfdi_regime_id'],
                'cfdi_use_id' => $payload['cfdi_use_id'],
            ]);

            if (! $payload['adding_member']) {
                $coverages = PlanCoverage::query()
                    ->where('plan_id', $payload['plan_id'])
                    ->get();

                foreach ($coverages as $coverage) {
                    PolicyService::query()->create([
                        'policy_id' => $policy->id,
                        'service_id' => $coverage->service_id,
                        'coupon_id' => null,
                        'included' => $coverage->events ?? 0,
                    ]);
                }

                $benefits = PlanBenefit::query()
                    ->where('plan_id', $payload['plan_id'])
                    ->get();

                foreach ($benefits as $benefit) {
                    PolicyService::query()->create([
                        'policy_id' => $policy->id,
                        'service_id' => null,
                        'coupon_id' => $benefit->coupon_id,
                        'included' => $benefit->events ?? 0,
                    ]);
                }
            }

            return $policy->load('user');
        });
    }

    /**
     * Determine the policy number based on plan and parent policy.
     */
    private function buildPolicyNumber(int $planId, ?int $parentPolicyId): string
    {
        if ($parentPolicyId) {
            $parentNumber = Policy::query()->whereKey($parentPolicyId)->value('number');
            $next = Policy::query()->where('parent_policy_id', $parentPolicyId)->count() + 1;
            $suffix = str_pad((string) $next, 2, '0', STR_PAD_LEFT);

            return "{$parentNumber}-{$suffix}";
        }

        $year = Carbon::now()->year;
        $shortYear = Carbon::now()->format('y');
        $next = Policy::query()->where('plan_id', $planId)->whereYear('created_at', $year)->count() + 1;
        $number = str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        $plan = str_pad((string) $planId, 2, '0', STR_PAD_LEFT);

        return "INX{$shortYear}IN{$plan}-{$number}";
    }
}
