<?php

namespace App\Services\Policies;

use App\Models\PlanBenefit;
use App\Models\Policy;
use App\Models\PolicyService;
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

            if (! $payload['adding_member']) {
                $benefits = PlanBenefit::query()
                    ->where('plan_id', $payload['plan_id'])
                    ->orderBy('service_id')
                    ->get();

                foreach ($benefits as $benefit) {
                    PolicyService::query()->create([
                        'policy_id' => $policy->id,
                        'service_id' => $benefit->service_id,
                        'included' => $benefit->events,
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
