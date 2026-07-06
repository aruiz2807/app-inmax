<?php

namespace App\Livewire\Clerk;

use App\Enums\MedicationMovementType;

use App\Models\Appointment;
use App\Models\Parameter;
use App\Models\PolicyService;
use App\Models\User;
use App\Models\MedicationMovement;

use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Component;
use Livewire\Attributes\On;

class CheckoutModal extends Component
{
    public ?int $appointmentId = null;
    public ?Appointment $appointment = null;
    public ?User $user = null;
    public $prescriptions = [];
    public array $deliveryQuantities = [];
    public array $requiredQuantities = [];
    
    public bool $useCoupon = false;
    public bool $hasCouponAvailable = false;
    public float $couponValue = 0;

    public bool $useMembersDiscount = false;
    public bool $isMembershipActive = false;
    public float $membersDiscountPercentage = 0;

    public bool $isPartialDispensation = false;

    public function render()
    {
        return view('livewire.clerk.checkout-modal');
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
        $subtotalPublic = 0;
        $subtotalMembers = 0;

        foreach ($this->prescriptions as $prescription) 
        {
            if (!is_null($prescription->medication_id) && $prescription->status === 'Prescribed') {
                $quantity = (int) ($this->requiredQuantities[$prescription->id] ?? 0);
                $subtotalPublic += $quantity * $prescription->medication->price_public;
                $subtotalMembers += $quantity * $prescription->medication->price_members;
            }
        }

        if ($this->useMembersDiscount && $this->isMembershipActive) 
        {
            $total = $subtotalMembers;
        } 
        else 
        {
            $total = $subtotalPublic;

            if ($this->useCoupon && $this->hasCouponAvailable) 
            {
                $total -= $this->couponValue;
            }
        }

        return max(0, $total);
    }

    public function getCanDispenseProperty()
    {
        if ($this->isAppointmentDispensed) {
            return false;
        }

        $hasQuantity = collect($this->deliveryQuantities)->map(fn($qty) => (int) $qty)->sum() > 0;
        $hasBenefitSelected = $this->useCoupon || $this->useMembersDiscount;

        if ($this->total == 0) {
            return $hasQuantity;
        }

        if($this->isPartialDispensation) {
            return true;
        }

        return $hasQuantity && $hasBenefitSelected;
    }

    public function getIsAppointmentDispensedProperty(): bool
    {
        if (empty($this->prescriptions)) {
            return false;
        }

        return collect($this->prescriptions)->every(function ($prescription) {
            return (string) $prescription->status === 'Dispensed';
        });
    }

    public function getShowCheckoutBenefitsProperty(): bool
    {
        return ! $this->isPartialDispensation && ! $this->isAppointmentDispensed;
    }

    public function getShowDispenseActionProperty(): bool
    {
        return ! $this->isAppointmentDispensed;
    }

    #[On('openPrescription')]
    public function open_prescription($appointmentId)
    {
        $this->appointmentId = $appointmentId;

        $appointment = Appointment::with(['user.policy', 'prescriptions.medication'])->find($this->appointmentId);
        $this->appointment = $appointment;
        $this->user = $appointment?->user;
        $this->prescriptions = $appointment?->prescriptions ?? [];
        $this->isPartialDispensation = $appointment?->status_prescription === 'Partial';

        $this->deliveryQuantities = [];
        $this->requiredQuantities = [];
        foreach ($this->prescriptions as $prescription) 
        {
            $deliveredSoFar = (int) ($prescription->delivered_quantity ?? 0);
            $requiredTotal = (int) ($prescription->required_quantity ?? 0);
            $pending = max(0, $requiredTotal - $deliveredSoFar);

            if($prescription->status === 'Dispensed')
            {
                $this->deliveryQuantities[$prescription->id] = $prescription->delivered_quantity;
            }
            elseif (!is_null($prescription->required_quantity))
            {
                $this->deliveryQuantities[$prescription->id] = $pending;
            }
            else
            {
                $this->deliveryQuantities[$prescription->id] = 1;
            }
            $this->requiredQuantities[$prescription->id] = $prescription->required_quantity ?? 1;
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
        
        if ($param && !empty($param->value)) 
        {
            $valueParam = Parameter::where('type', 'CP')->where('key', 'Valor')->first();

            if ($valueParam && is_numeric($valueParam->value)) 
            {
                $this->couponValue = (float) $valueParam->value;
            }

            $policyId = $this->user->policy->type === 'Member' 
                ? $this->user->policy->parent_policy_id 
                : $this->user->policy->id;

            $policyService = PolicyService::where('policy_id', $policyId)
                ->where('service_id', $param->value)
                ->first();

            if ($policyService && ($policyService->included - $policyService->used) > 0) 
            {
                $this->hasCouponAvailable = true;
            }
        }

        // Fetch Members Discount Percentage
        $discountParam = Parameter::where('type', 'DM')->where('key', 'Descuento')->first();

        if ($discountParam && is_numeric($discountParam->value)) 
        {
            $this->membersDiscountPercentage = (float) $discountParam->value;
        }
    }

    public function dispense()
    {
        if (!$this->appointmentId) {
            return;
        }

        $shouldPrintTicket = false;

        $appointment = Appointment::with('prescriptions')->find($this->appointmentId);
        $currentPrescriptions = $appointment->prescriptions->keyBy('id');

        $hasDispensedChanges = false;

        foreach ($this->prescriptions as $prescription) 
        {
            $currentPrescription = $currentPrescriptions->get($prescription->id);

            if (!$currentPrescription) {
                continue;
            }

            if ((string) $currentPrescription->status === 'Dispensed') {
                continue;
            }

            $qty = (int) ($this->deliveryQuantities[$prescription->id] ?? 0);
            $reqQty = (int) ($this->requiredQuantities[$prescription->id] ?? ($currentPrescription->required_quantity ?? 0));
            $deliveredSoFar = (int) ($currentPrescription->delivered_quantity ?? 0);

            if($reqQty > 0) 
            {
                $currentPrescription->update([
                    'required_quantity' => $reqQty,
                ]);
            }

            $pending = max(0, $reqQty - $deliveredSoFar);
            $qty = max(0, min($qty, $pending));
            
            if ($qty > 0) {
                $newDelivered = $deliveredSoFar + $qty;
                $isFullyDispensed = $reqQty > 0 && $newDelivered >= $reqQty;

                $currentPrescription->update([
                    'status' => $isFullyDispensed ? 'Dispensed' : 'Prescribed',
                    'delivered_quantity' => $newDelivered,
                    'required_quantity' => $reqQty,
                ]);

                //update medications_movements table
                if (!is_null($currentPrescription->medication_id)) {
                    MedicationMovement::create([
                        'medication_id' => $currentPrescription->medication_id,
                        'type' => MedicationMovementType::OUT,
                        'adjustment' => 0,
                        'quantity' => $qty,
                        'reference' => "Receta surtida - Cita #{$appointment->id}",
                        'prescription_id' => $currentPrescription->id,
                        //'medication_purchase_id' => null,
                        'user_id' => $this->user?->id,
                    ]);
                }
                

                $hasDispensedChanges = true;
            }
        }

        if ($hasDispensedChanges) 
        {
            $appointment->load('prescriptions');

            $allDispensed = $appointment->prescriptions->every(function ($prescription) {
                return (string) $prescription->status === 'Dispensed';
            });

            $appointment->update([
                'status_prescription' => $allDispensed ? 'Filled' : 'Partial',
            ]);

            if ($this->useCoupon && $this->hasCouponAvailable) 
            {
                $param = Parameter::where('type', 'CP')->where('key', 'Medicamentos')->first();

                if ($param && !empty($param->value)) 
                {
                    $policyId = $this->user->policy->type === 'Member' 
                        ? $this->user->policy->parent_policy_id 
                        : $this->user->policy->id;

                    $policyService = PolicyService::where('policy_id', $policyId)
                        ->where('service_id', $param->value)
                        ->first();

                    if ($policyService) 
                    {
                        $policyService->increment('used');
                    }
                }
            }

            $this->dispatch('notify', type: 'success', content: 'Medicamentos surtidos correctamente.');
            $this->dispatch('pg:eventRefresh-dispensationTable');
        }

        $this->dispatch('close-checkout-modal');

        if (!$this->isPartialDispensation) 
        {
            $this->dispatch('print-checkout-ticket');
        }
    }

    public function print_ticket()
    {
        if (! $this->appointmentId) 
        {
            return;
        }

        $appointment = Appointment::query()
            ->with(['user', 'doctor.user', 'prescriptions.medication'])
            ->find($this->appointmentId);

        if (! $appointment) 
        {
            return;
        }

        $rows = collect($appointment->prescriptions)
            ->map(function ($prescription) {
                /*if ((string) $prescription->status !== 'Dispensed') 
                {
                    return null;
                }*/

                $quantity = (int) ($prescription->required_quantity ?? 0);

                if ($quantity <= 0) 
                {
                    return null;
                }

                // If manual medication skip
                if (is_null($prescription->medication_id)) {
                    return null;
                }

                $pricePublic = (float) $prescription->medication->price_public;
                $priceMembers = (float) $prescription->medication->price_members;

                $unitPrice = ($this->useMembersDiscount && $this->isMembershipActive)
                    ? $priceMembers
                    : $pricePublic;

                return [
                    'name' => (string) $prescription->medication->name,
                    'trade_name' => (string) $prescription->medication->trade_name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $unitPrice * $quantity,
                    'line_total_public' => $pricePublic * $quantity,
                ];
            })
            ->filter()
            ->values();

        if ($rows->isEmpty()) 
        {
            return;
        }

        $subtotalPublic = (float) $rows->sum('line_total_public');
        $subtotalCharged = (float) $rows->sum('line_total');

        $discount = 0.0;
        $discountLabel = null;

        if ($this->useMembersDiscount && $this->isMembershipActive) 
        {
            $discount = max(0, $subtotalPublic - $subtotalCharged);
            $discountLabel = 'Precio preferencial';
        } 
        elseif ($this->useCoupon && $this->hasCouponAvailable) 
        {
            $discount = min((float) $this->couponValue, $subtotalPublic);
            $discountLabel = 'Cupon aplicado';
        }

        $total = ($this->useMembersDiscount && $this->isMembershipActive)
            ? $subtotalCharged
            : max(0, $subtotalPublic - $discount);

        $discountApplied = $discount > 0;
        $discountType = $discountApplied ? $discountLabel : 'Sin descuento';

        $pdf = Pdf::loadView('pdf.checkout-ticket', [
            'appointment' => $appointment,
            'rows' => $rows,
            'subtotalPublic' => number_format($subtotalPublic, 2),
            'subtotalCharged' => number_format($subtotalCharged, 2),
            'discount' => number_format($discount, 2),
            'discountLabel' => $discountLabel,
            'discountApplied' => $discountApplied,
            'discountType' => $discountType,
            'total' => number_format($total, 2),
            'benefitLabel' => $this->useMembersDiscount ? 'Membresia' : ($this->useCoupon ? 'Cupon' : 'Sin beneficio'),
            'contactEmail' => \App\Models\Parameter::where('type', 'RS')->where('key', 'Email')->value('value') ?? 'contacto@inmax.com'
        ])->setPaper([0, 0, 226, 567], 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "ticket-receta-" . str_pad((string) (int) $appointment->id, 5, '0', STR_PAD_LEFT) . ".pdf"
        );
    }
}