<?php

namespace App\Livewire\Policies;

use App\Livewire\Forms\GroupPolicyForm;
use App\Models\Plan;
use App\Models\Policy;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Carbon\Carbon;

class GroupPolicyPage extends Component
{
    public GroupPolicyForm $form;
    public ?int $policyId = null;
    public $plans = [];

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.policies.group-policy-page');
    }

    public function mount($policyId)
    {
        if($policyId)
        {
            $this->policyId = $policyId;
            $policy = Policy::find($policyId);

            $this->form->set($policy);
        }

        $this->plans = Plan::orderBy('name')->where([
            ['type', 'Group'],
            ['status', 'Active'],
        ])->get();
    }

    public function save()
    {
        if($this->policyId)
        {
            $this->form->update($this->policyId);
        }
        else
        {
            $this->form->store();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Poliza almacenada exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-policy-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-policiesTable');

        //clear form
        $this->resetForm();
    }

    #[Computed]
    public function age()
    {
        if(!$this->form->birth)
        {
            return null;
        }

        try
        {
            return Carbon::parse($this->form->birth)->age;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    public function resetForm()
    {
        $this->form->reset();
    }
}
