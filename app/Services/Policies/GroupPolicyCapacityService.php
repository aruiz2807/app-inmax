<?php

namespace App\Services\Policies;

use App\Models\Policy;
use App\Models\PolicyPreregistration;
use InvalidArgumentException;

class GroupPolicyCapacityService
{
    /**
     * Resolve a valid collective root policy.
     */
    public function resolveGroupPolicy(int $policyId, bool $lockForUpdate = false): Policy
    {
        $query = Policy::query()
            ->with(['plan:id,name,type', 'user:id,name,company_id', 'user.company:id,name'])
            ->whereKey($policyId)
            ->whereNull('parent_policy_id')
            ->where('type', 'Group')
            ->where('status', '!=', 'Cancelled');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $policy = $query->first();

        if (! $policy) {
            throw new InvalidArgumentException('La poliza colectiva seleccionada no esta disponible para preregistro.');
        }

        return $policy;
    }

    /**
     * Current occupancy summary for a collective policy.
     *
     * @return array{
     *     total_slots: int,
     *     registered_members: int,
     *     pending_preregistrations: int,
     *     available_slots: int
     * }
     */
    public function summary(Policy|int $groupPolicy, ?int $ignorePreregistrationId = null): array
    {
        $policy = $groupPolicy instanceof Policy
            ? $groupPolicy
            : $this->resolveGroupPolicy($groupPolicy);

        $registeredMembers = Policy::query()
            ->where('parent_policy_id', $policy->id)
            ->where('status', 'Active')
            ->count();

        $pendingPreregistrations = PolicyPreregistration::query()
            ->where('preregistration_type', PolicyPreregistration::TYPE_GROUP_MEMBER)
            ->where('parent_policy_id', $policy->id)
            ->whereNull('used_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', now())
            ->when($ignorePreregistrationId, function ($query) use ($ignorePreregistrationId) {
                $query->whereKeyNot($ignorePreregistrationId);
            })
            ->count();

        $totalSlots = (int) ($policy->members ?? 0);
        $availableSlots = max($totalSlots - $registeredMembers - $pendingPreregistrations, 0);

        return [
            'total_slots' => $totalSlots,
            'registered_members' => $registeredMembers,
            'pending_preregistrations' => $pendingPreregistrations,
            'available_slots' => $availableSlots,
        ];
    }

    /**
     * Ensure there is at least one free slot in the collective policy.
     */
    public function assertHasAvailableSlot(Policy|int $groupPolicy, ?int $ignorePreregistrationId = null): array
    {
        $summary = $this->summary($groupPolicy, $ignorePreregistrationId);

        if ($summary['available_slots'] < 1) {
            throw new InvalidArgumentException('La poliza colectiva seleccionada ya no tiene lugares disponibles.');
        }

        return $summary;
    }
}
