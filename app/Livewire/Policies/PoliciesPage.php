<?php

namespace App\Livewire\Policies;

use App\Models\Policy;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class PoliciesPage extends Component
{
    public ?int $policyId = null;

    public ?string $policyType = null;

    public bool $newMember = false;

    public string $policy_number = '';

    public string $policy_user_name = '';

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.policies.policies-page');
    }

    #[On('editPolicy')]
    public function edit(int $policyId): void
    {
        $policy = Policy::query()->findOrFail($policyId);

        $this->policyType = $policy->plan->type;
        $this->policyId = $policyId;

        $this->dispatch('open-policy-modal');
    }

    #[On('addMember')]
    public function addMember(int $policyId): void
    {
        $this->policyType = 'Individual';
        $this->policyId = $policyId;
        $this->newMember = true;

        $this->dispatch('open-policy-modal');
    }

    #[On('activatePolicy')]
    public function activate(int $policyId): void
    {
        $policy = Policy::query()->findOrFail($policyId);

        $this->policyId = $policyId;
        $this->policy_number = $policy->number;
        $this->policy_user_name = $policy->user->name;

        $this->dispatch('open-activation-modal');
    }

    #[On('deactivatePolicy')]
    public function deactivate(int $policyId): void
    {
        $policy = Policy::query()->findOrFail($policyId);

        $this->policyId = $policyId;
        $this->policy_number = $policy->number;
        $this->policy_user_name = $policy->user->name;

        $this->dispatch('open-deactivation-modal');
    }

    #[On('cancelPolicy')]
    public function cancel(int $policyId): void
    {
        $policy = Policy::query()->findOrFail($policyId);

        $this->policyId = $policyId;
        $this->policy_number = $policy->number;
        $this->policy_user_name = $policy->user->name;

        $this->dispatch('open-cancel-modal');
    }

    public function confirmActivation(): void
    {
        $policy = Policy::query()->findOrFail($this->policyId);
        $start = Carbon::now()->addDays(5);
        $end = Carbon::now()->addDays(5)->addYear();

        $policy->update([
            'status' => 'Active',
            'start_date' => $start,
            'end_date' => $end,
        ]);

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Poliza activada exitosamente!',
            duration: 4000
        );

        $this->dispatch('close-activation-modal');
        $this->dispatch('pg:eventRefresh-policiesTable');
    }

    public function confirmDeactivation(): void
    {
        $policy = Policy::query()->findOrFail($this->policyId);

        $policy->update([
            'status' => 'Inactive',
        ]);

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Poliza desactivada exitosamente!',
            duration: 4000
        );

        $this->dispatch('close-deactivation-modal');
        $this->dispatch('pg:eventRefresh-policiesTable');
    }

    public function confirmCancel(): void
    {
        $policy = Policy::query()->findOrFail($this->policyId);

        $policy->update([
            'status' => 'Cancelled',
            'end_date' => Carbon::now(),
        ]);

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Poliza cancelada exitosamente!',
            duration: 4000
        );

        $this->dispatch('close-cancel-modal');
        $this->dispatch('pg:eventRefresh-policiesTable');
    }

    public function selectType(string $type): void
    {
        $this->policyType = $type;
    }

    public function resetForm(): void
    {
        $this->policyId = null;
        $this->policyType = null;
        $this->newMember = false;
    }
}
