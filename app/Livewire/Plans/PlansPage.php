<?php

namespace App\Livewire\Plans;

use App\Livewire\Forms\PlansForm;
use App\Models\Plan;
use App\Models\PlanBenefit;
use App\Models\Service;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class PlansPage extends Component
{
    public PlansForm $form;
    public ?int $planId = null;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.plans.plans-page');
    }

    #[On('editPlan')]
    public function edit($planId)
    {
        $plan = Plan::find($planId);

        $this->form->set($plan);
        $this->planId = $planId;

        //open modal
        $this->dispatch('open-plan-modal');
    }

    public function save()
    {
        if($this->planId)
        {
            $this->form->update($this->planId);
        }
        else
        {
            $this->form->store();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Plan de cobetura almacenada exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-plan-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-plansTable');

        //clear form
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->form->reset();
        $this->planId = null;
    }
}
