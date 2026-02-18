<?php

namespace App\Livewire\Policies;

use App\Livewire\Forms\PoliciesForm;
use App\Models\Plan;
use App\Models\Policy;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Carbon\Carbon;

class PoliciesPage extends Component
{
    public PoliciesForm $form;
    public ?int $policyId = null;
    public ?string $policyType = null;
    public $newMember = false;

    public $policy_number = '';
    public $policy_user_name = '';

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.policies.policies-page');
    }

    #[On('editPolicy')]
    public function edit($policyId)
    {
        $policy = Policy::find($policyId);

        $this->policyType = $policy->plan->type;
        $this->policyId = $policyId;

        //open modal
        $this->dispatch('open-policy-modal');
    }

    #[On('addMember')]
    public function addMember($policyId)
    {
        $this->policyType = 'Individual';
        $this->policyId = $policyId;
        $this->newMember = true;

        //open modal
        $this->dispatch('open-policy-modal');
    }

    #[On('activatePolicy')]
    public function activate($policyId)
    {
        $policy = Policy::find($policyId);

        $this->policyId = $policyId;
        $this->policy_number = $policy->number;
        $this->policy_user_name = $policy->user->name;

        //open new modal
        $this->dispatch('open-activation-modal');

    }

    #[On('deactivatePolicy')]
    public function deactivate($policyId)
    {
        $policy = Policy::find($policyId);

        $this->policyId = $policyId;
        $this->policy_number = $policy->number;
        $this->policy_user_name = $policy->user->name;

        //open modal
        $this->dispatch('open-deactivation-modal');

    }

    #[On('cancelPolicy')]
    public function cancel($policyId)
    {
        $policy = Policy::find($policyId);

        $this->policyId = $policyId;
        $this->policy_number = $policy->number;
        $this->policy_user_name = $policy->user->name;

        //open modal
        $this->dispatch('open-cancel-modal');

    }

    public function confirmActivation()
    {
        $policy = Policy::findOrFail($this->policyId);

        $start = Carbon::now()->addDays(5);
        $end = Carbon::now()->addDays(5)->addYear();

        $policy->update([
            'status' => 'Active',
            'start_date' => $start,
            'end_date' => $end,
        ]);

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Poliza activada exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-activation-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-policiesTable');
    }

    public function confirmDeactivation()
    {
        $policy = Policy::findOrFail($this->policyId);

        $policy->update([
            'status' => 'Inactive',
        ]);

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Poliza desactivada exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-deactivation-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-policiesTable');
    }

    public function confirmCancel()
    {
        $policy = Policy::findOrFail($this->policyId);
        $now = Carbon::now();

        $policy->update([
            'status' => 'Cancelled',
            'end_date' => $now,
        ]);

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Poliza cancelada exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-cancel-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-policiesTable');
    }

    public function selectType($type)
    {
        $this->policyType = $type;
    }

    public function resetForm()
    {
        $this->form->reset();
        $this->policyId = null;
        $this->policyType = null;
        $this->newMember = false;
    }
}
