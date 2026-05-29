<?php

namespace App\Livewire\Plans;

use App\Models\PlanBenefit;
use App\Models\Coupon;
use App\Models\DoctorService;
use App\Models\DoctorCoupon;
use Livewire\Component;
use Livewire\Attributes\On;

class PlanBenefitsModal extends Component
{
    public ?int $planId = null;
    public string $benefitType = 'General'; // 'Service' or 'Coupon' or 'General'
    public ?int $selectedBenefitId = null;

    public $availableDoctorServices = [];
    public $availableDoctorCoupons = [];
    public $availableGeneralCoupons = [];
    public $benefits = [];
    
    public $events = [];

    public function render()
    {
        return view('livewire.plans.plan-benefits-modal');
    }

    public function mount()
    {
        $this->availableGeneralCoupons = Coupon::with(['service'])->get();
        $this->availableDoctorServices = DoctorService::with(['service', 'doctor.user'])->get();
        $this->availableDoctorCoupons = DoctorCoupon::with(['coupon', 'doctor.user'])->get();
    }

    #[On('editBenefits')]
    public function editBenefits($planId)
    {
        $this->planId = $planId;
        $this->selectedBenefitId = null;
        $this->benefitType = 'General';

        $this->loadBenefitsAndAvailable();

        $this->dispatch('open-plan-benefits-modal');
    }

    public function addBenefit()
    {
        if (!$this->selectedBenefitId) {
            return;
        }

        $data = [
            'plan_id' => $this->planId,
        ];

        if ($this->benefitType === 'Service') {
            $data['doctor_service_id'] = $this->selectedBenefitId;
        } else if ($this->benefitType === 'Coupon') {
            $data['doctor_coupon_id'] = $this->selectedBenefitId;
        } else {
            $data['coupon_id'] = $this->selectedBenefitId;
        }

        PlanBenefit::create($data);

        $this->selectedBenefitId = null;
        $this->loadBenefitsAndAvailable();
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

        $this->loadBenefitsAndAvailable();
    }

    public function setBenefitType($type)
    {
        $this->benefitType = $type;
        $this->selectedBenefitId = null;
    }

    private function loadBenefitsAndAvailable()
    {
        if (!$this->planId) return;

        $this->benefits = PlanBenefit::with([
                'coupon.service', 
                'doctorService.service', 
                'doctorService.doctor.user', 
                'doctorCoupon.coupon', 
                'doctorCoupon.doctor.user'
            ])
            ->where('plan_id', $this->planId)
            ->get()
            ->sortBy(fn($benefit) => $benefit->doctor_coupon_id ? 0 : 1)
            ->values();

        $this->availableDoctorServices = DoctorService::with(['service', 'doctor.user'])
            ->whereHas('service', fn($q) => $q->where('status', 'Active'))
            ->whereDoesntHave('planBenefits', fn ($query) =>
                $query->where('plan_id', $this->planId)
            )
            ->get();

        $this->availableDoctorCoupons = DoctorCoupon::with(['coupon', 'doctor.user'])
            ->whereDoesntHave('planBenefits', fn ($query) =>
                $query->where('plan_id', $this->planId)
            )
            ->get();

        $this->availableGeneralCoupons = Coupon::with(['service'])
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
