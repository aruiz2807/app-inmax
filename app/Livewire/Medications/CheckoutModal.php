<?php

namespace App\Livewire\Medications;

use App\Models\Appointment;
use App\Models\Parameter;
use App\Models\PolicyService;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;

class CheckoutModal extends Component
{
    public ?int $appointmentId = null;
    public ?User $user = null;
    public $prescriptions = [];
    public array $deliveryQuantities = [];
    
    public bool $useCoupon = false;
    public bool $hasCouponAvailable = false;
    public float $couponValue = 0;

    public bool $useMembersDiscount = false;
    public bool $isMembershipActive = false;
    public float $membersDiscountPercentage = 0;

    public function render()
    {
        return view('livewire.medications.checkout-modal');
    }

    public function mount()
    {
        
    }

    public function updatedUseCoupon($value)
    {
        if ($value) {
            $this->useMembersDiscount = false;
        }
    }

    public function updatedUseMembersDiscount($value)
    {
        if ($value) {
            $this->useCoupon = false;
        }
    }

    public function getTotalProperty()
    {
        $subtotal = 0;
        foreach ($this->prescriptions as $prescription) {
            $quantity = (int) ($this->deliveryQuantities[$prescription->id] ?? 0);
            $subtotal += $quantity * $prescription->medication->price_members;
        }

        $total = $subtotal;

        if ($this->useCoupon && $this->hasCouponAvailable) {
            $total -= $this->couponValue;
        } elseif ($this->useMembersDiscount && $this->isMembershipActive) {
            $total -= ($subtotal * ($this->membersDiscountPercentage / 100));
        }

        return max(0, $total);
    }

    #[On('openPrescription')]
    public function open_prescription($appointment_id)
    {
        $this->appointmentId = $appointment_id;

        $appointment = Appointment::with(['user.policy', 'prescriptions.medication'])->find($this->appointmentId);
        $this->user = $appointment?->user;
        $this->prescriptions = $appointment?->prescriptions ?? [];

        $this->deliveryQuantities = [];
        foreach ($this->prescriptions as $prescription) {
            $this->deliveryQuantities[$prescription->id] = $prescription->quantity;
        }

        $this->checkDiscountsAvailability();

        //open modal
        $this->dispatch('open-checkout-modal');
    }

    protected function checkDiscountsAvailability()
    {
        $this->useCoupon = false;
        $this->hasCouponAvailable = false;
        $this->couponValue = 0;

        $this->useMembersDiscount = false;
        $this->isMembershipActive = false;
        $this->membersDiscountPercentage = 0;

        if (!$this->user || !$this->user->policy) {
            return;
        }

        // Check Membership Status
        $this->isMembershipActive = $this->user->policy->status === 'Active';

        // Fetch Coupon Params
        $param = Parameter::where('type', 'CP')->where('key', 'Medicamentos')->first();
        if ($param && !empty($param->value)) {
            $valueParam = Parameter::where('type', 'CP')->where('key', 'Valor')->first();
            if ($valueParam && is_numeric($valueParam->value)) {
                $this->couponValue = (float) $valueParam->value;
            }

            $policyId = $this->user->policy->type === 'Member' 
                ? $this->user->policy->parent_policy_id 
                : $this->user->policy->id;

            $policyService = PolicyService::where('policy_id', $policyId)
                ->where('service_id', $param->value)
                ->first();

            if ($policyService && ($policyService->included - $policyService->used) > 0) {
                $this->hasCouponAvailable = true;
            }
        }

        // Fetch Members Discount Percentage
        $discountParam = Parameter::where('type', 'DM')->where('key', 'Descuento')->first();
        if ($discountParam && is_numeric($discountParam->value)) {
            $this->membersDiscountPercentage = (float) $discountParam->value;
        }
    }
}