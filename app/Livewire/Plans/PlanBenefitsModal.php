<?php

namespace App\Livewire\Plans;

use App\Models\PlanBenefit;
use App\Models\Service;
use Livewire\Component;
use Livewire\Attributes\On;

class PlanBenefitsModal extends Component
{
    public ?int $planId = null;
    public ?int $serviceId = null;

    public $services = [];
    public $benefits = [];
    public $values = [];

    public function mount()
    {
        $this->services = Service::query()->where('status', 'Active')->get();
    }

    #[On('editBenefits')]
    public function editBenefits($planId)
    {
        $this->planId = $planId;
        $this->serviceId = null;

        $this->loadBenefitsAndServices();

        $this->dispatch('open-plan-benefits-modal');
    }

    public function addBenefit()
    {
        if (!$this->serviceId) {
            return;
        }

        PlanBenefit::create([
            'plan_id' => $this->planId,
            'service_id' => $this->serviceId,
        ]);

        $this->loadBenefitsAndServices();
    }

    public function updateBenefits()
    {
        foreach ($this->benefits as $benefit)
        {
            if ($benefit->service->type === 'Amount')
            {
                $benefit->amount = str_replace(',', '', $this->values[$benefit->id] ?? 0);
            }
            elseif ($benefit->service->type === 'Event')
            {
                $benefit->events = $this->values[$benefit->id] ?? 0;
            }

            $benefit->save();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Plan de cobetura almacenado exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-plan-benefits-modal');
    }

    public function delete($benefitId)
    {
        PlanBenefit::whereKey($benefitId)->delete();

        $this->loadBenefitsAndServices();
    }

    private function loadBenefitsAndServices()
    {
        $this->benefits = PlanBenefit::with('service:id,name,type')
            ->where('plan_id', $this->planId)
            ->get();

        $this->services = Service::query()
            ->where('status', 'Active')
            ->whereDoesntHave('benefits', fn ($query) =>
                $query->where('plan_id', $this->planId)
            )
            ->get();

        $this->initializeValues();
    }

    private function initializeValues(): void
    {
        $this->values = [];

        foreach ($this->benefits as $benefit)
        {
            $value = match ($benefit->service->type) {
                'Amount' => $benefit->amount,
                'Event'  => $benefit->events,
                default  => null,
            };

            $this->values[$benefit->id] = $value !== null ? (string) $value : '0';
        }
    }

    public function render()
    {
        return view('livewire.plans.plan-benefits-modal');
    }
}
