<?php

namespace App\Livewire\Plans;

use App\Models\PlanCoverage;
use App\Models\Service;
use Livewire\Component;
use Livewire\Attributes\On;

class PlanCoverageModal extends Component
{
    public ?int $planId = null;
    public ?int $serviceId = null;

    public $services = [];
    public $coverage = [];
    public $values = [];

    public function render()
    {
        return view('livewire.plans.plan-coverage-modal');
    }

    public function mount()
    {
        $this->services = Service::query()->where('status', 'Active')->get();
    }

    #[On('editCoverage')]
    public function editCoverage($planId)
    {
        $this->planId = $planId;
        $this->serviceId = null;

        $this->loadCoverageAndServices();

        $this->dispatch('open-plan-coverage-modal');
    }

    public function addCoverage()
    {
        if (!$this->serviceId) {
            return;
        }

        PlanCoverage::create([
            'plan_id' => $this->planId,
            'service_id' => $this->serviceId,
        ]);

        $this->loadCoverageAndServices();
    }

    public function updateCoverage()
    {
        foreach ($this->coverage as $included)
        {
            if ($included->service->type === 'Amount')
            {
                $included->amount = str_replace(',', '', $this->values[$included->id] ?? 0);
            }
            elseif ($included->service->type === 'Event')
            {
                $included->events = $this->values[$included->id] ?? 0;
            }

            $included->save();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Plan de cobetura almacenado exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-plan-coverage-modal');
    }

    public function delete($coverageId)
    {
        PlanCoverage::whereKey($coverageId)->delete();

        $this->loadCoverageAndServices();
    }

    private function loadCoverageAndServices()
    {
        $this->coverage = PlanCoverage::with('service:id,name,type')
            ->where('plan_id', $this->planId)
            ->get();

        $this->services = Service::query()
            ->where('status', 'Active')
            ->whereDoesntHave('coverage', fn ($query) =>
                $query->where('plan_id', $this->planId)
            )
            ->get();

        $this->initializeValues();
    }

    private function initializeValues(): void
    {
        $this->values = [];

        foreach ($this->coverage as $included)
        {
            $value = match ($included->service->type) {
                'Amount' => $included->amount,
                'Event'  => $included->events,
                default  => null,
            };

            $this->values[$included->id] = $value !== null ? (string) $value : '0';
        }
    }
}