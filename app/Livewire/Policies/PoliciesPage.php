<?php

namespace App\Livewire\Policies;

use App\Livewire\Forms\PoliciesForm;
use App\Models\Plan;
use App\Models\Policy;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class PoliciesPage extends Component
{
    public PoliciesForm $form;
    public ?int $policyId = null;
    public ?string $policyType = null;

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

    public function selectType($type)
    {
        $this->policyType = $type;
    }

    public function resetForm()
    {
        $this->form->reset();
        $this->policyId = null;
        $this->policyType = null;
    }
}
