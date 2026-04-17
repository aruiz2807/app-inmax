<?php

namespace App\Services\Policies;

use App\Models\Company;
use App\Models\PlanBenefit;
use App\Models\Policy;
use App\Models\PolicyService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GroupPolicyRegistrationService
{
    /**
     * Create a collective policy with its company, representative user and included services.
     *
     * @param  array{
     *     company: string,
     *     type: string,
     *     legal_name: string,
     *     rfc: string,
     *     name: string,
     *     email: string,
     *     phone: string,
     *     birth: string,
     *     curp: string|null,
     *     passport: string,
     *     plan_id: int,
     *     sales_user_id: int|null,
     *     insurance: array<int, string>,
     *     members: int,
     *     policy_preregistration_id?: int|null
     * }  $payload
     */
    public function create(array $payload): Policy
    {
        return DB::transaction(function () use ($payload) {
            $company = Company::query()->create([
                'name' => $payload['company'],
                'type' => $payload['type'],
                'legal_name' => $payload['legal_name'],
                'rfc' => $payload['rfc'],
            ]);

            $user = User::query()->create([
                'name' => $payload['name'],
                'profile' => 'User',
                'email' => $payload['email'],
                'phone' => $payload['phone'],
                'birth_date' => $payload['birth'],
                'curp' => $payload['curp'],
                'passport' => $payload['passport'],
                'company_id' => $company->id,
                'password' => Hash::make($payload['phone']),
            ]);

            $policy = Policy::query()->create([
                'user_id' => $user->id,
                'sales_user_id' => $payload['sales_user_id'],
                'plan_id' => $payload['plan_id'],
                'policy_preregistration_id' => $payload['policy_preregistration_id'] ?? null,
                'number' => $this->buildPolicyNumber($payload['plan_id']),
                'type' => 'Group',
                'members' => $payload['members'],
                'insurance' => $payload['insurance'],
            ]);

            $benefits = PlanBenefit::query()
                ->where('plan_id', $payload['plan_id'])
                ->orderBy('service_id')
                ->get();

            foreach ($benefits as $benefit) {
                PolicyService::query()->create([
                    'policy_id' => $policy->id,
                    'service_id' => $benefit->service_id,
                    'included' => (int) round(($benefit->events * $payload['members']) / 2),
                ]);
            }

            return $policy->load(['user.company', 'plan']);
        });
    }

    private function buildPolicyNumber(int $planId): string
    {
        $year = Carbon::now()->year;
        $shortYear = Carbon::now()->format('y');
        $next = Policy::query()->where('plan_id', $planId)->whereYear('created_at', $year)->count() + 1;
        $number = str_pad((string) $next, 5, '0', STR_PAD_LEFT);
        $plan = str_pad((string) $planId, 2, '0', STR_PAD_LEFT);

        return "INX{$shortYear}GR{$plan}-{$number}";
    }
}
