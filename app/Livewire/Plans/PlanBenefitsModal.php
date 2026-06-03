<?php

namespace App\Livewire\Plans;

use App\Models\PlanBenefit;
use App\Models\Coupon;
use App\Models\DoctorService;
use Livewire\Component;
use Livewire\Attributes\On;

class PlanBenefitsModal extends Component
{
    public ?int $planId = null;
    public ?int $selectedBenefitId = null;

    public $availableServices = [];
    public $availableCoupons = [];
    public $benefits = [];
    
    public $events = [];

    public function render()
    {
        return view('livewire.plans.plan-benefits-modal');
    }

    public function mount()
    {
        $this->availableCoupons = Coupon::with(['service'])->get();
    }

    #[On('editBenefits')]
    public function editBenefits($planId)
    {
        $this->planId = $planId;
        $this->selectedBenefitId = null;

        $this->loadBenefitsAvailable();

        $this->dispatch('open-plan-benefits-modal');
    }

    public function addBenefit()
    {
        if (!$this->selectedBenefitId) {
            return;
        }

        $data = [
            'plan_id' => $this->planId,
            $data['coupon_id'] = $this->selectedBenefitId,
        ];

        PlanBenefit::create($data);

        $this->selectedBenefitId = null;
        $this->loadBenefitsAvailable();
    }

    public function updateBenefits()
    {
        foreach ($this->benefits as $benefit)
        {
            $benefit->events = $this->events[$benefit->id] ?? null;
            $benefit->save();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'¡Plan de cobertura almacenado exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-plan-benefits-modal');
    }

    public function delete($benefitId)
    {
        PlanBenefit::whereKey($benefitId)->delete();

        $this->loadBenefitsAvailable();
    }

    private function loadBenefitsAvailable()
    {
        if (!$this->planId) return;

        $this->benefits = PlanBenefit::with(['coupon.service'])
            ->where('plan_id', $this->planId)
            ->whereNotNull('coupon_id')
            ->get()
            ->values();

        $this->availableCoupons = Coupon::with(['service'])
            ->whereDoesntHave('planBenefits', fn ($query) =>
                $query->where('plan_id', $this->planId)
            )
            ->get();

        $this->initializeValues();
    }

    private function initializeValues(): void
    {
        $this->events = [];

        foreach ($this->benefits as $benefit)
        {
            $this->events[$benefit->id] = $benefit->events ?? 0;
        }
    }
}
