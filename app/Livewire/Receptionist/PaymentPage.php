<?php

namespace App\Livewire\Receptionist;

use App\Models\Appointment;
use App\Models\PolicyService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class PaymentPage extends Component
{
    public Appointment $appointment;
    public string $subtotal = '0.00';
    public string $user_payment = '0.00';
    public string $commision = '0.00';
    public string $total = '0.00';
    public bool $useCoupon = false;
    public bool $hasCouponAvailable = false;
    public bool $paymentSaved = false;
    public string $paymentSuccessMessage = '';
    public ?PolicyService $availableCouponBenefit = null;
    public float $couponDiscountValue = 0;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.receptionist.payment-page');
    }

    public function mount(Appointment $appointment): void
    {
        $this->appointment = $appointment->load(['user.policy', 'doctor.user', 'doctor.specialty', 'services']);

        if (! Auth::user()->staffDoctors()->whereKey($this->appointment->doctor_id)->exists()) {
            throw new AuthorizationException();
        }

        $this->subtotal = $this->formatMoney($this->appointment->subtotal);
        $this->user_payment = $this->formatMoney($this->appointment->user_payment);
        $this->commision = $this->formatMoney($this->appointment->commission);
        $this->total = $this->formatMoney($this->appointment->total);
        $this->useCoupon = (float) $this->appointment->coupon_discount > 0;

        $this->checkCouponAvailability();
        $this->calculateTotals();
    }

    public function updatedSubtotal(): void
    {
        $this->calculateTotals();
    }

    public function updatedUseCoupon(): void
    {
        $this->calculateTotals();
    }

    public function save(): void
    {
        if ($this->parseMoney($this->subtotal) <= 0) {
            $this->addError('subtotal', 'Ingrese un monto valido.');
            return;
        }

        $this->dispatch('open-payment-modal');
    }

    public function confirmPayment()
    {
        $subtotal = $this->parseMoney($this->subtotal);

        if ($subtotal <= 0) {
            $this->addError('subtotal', 'Ingrese un monto valido.');
            return;
        }

        $this->calculateTotals();

        if ($this->useCoupon && $this->availableCouponBenefit && (float) $this->appointment->coupon_discount <= 0) {
            $this->availableCouponBenefit->increment('used');
        }

        $this->appointment->update([
            'subtotal' => $subtotal,
            'coupon_discount' => $this->couponDiscountValue,
            'user_payment' => $this->parseMoney($this->user_payment),
            'commission' => $this->parseMoney($this->commision),
            'total' => $this->parseMoney($this->total),
        ]);

        $this->dispatch('close-payment-modal');
        $this->paymentSaved = true;
        $this->paymentSuccessMessage = 'Pago realizado con exito.';

        $this->dispatch(
            'notify',
            type: 'success',
            content: $this->paymentSuccessMessage,
            duration: 4000
        );
    }

    public function checkCouponAvailability(): void
    {
        $this->hasCouponAvailable = false;
        $this->availableCouponBenefit = null;

        $policy = $this->appointment->user->policy;

        if (! $policy) {
            return;
        }

        $policyId = $policy->type === 'Member' ? $policy->parent_policy_id : $policy->id;
        $serviceIds = $this->appointment->services->pluck('service_id')->toArray();

        $this->availableCouponBenefit = PolicyService::with('doctorCoupon.coupon')
            ->where('policy_id', $policyId)
            ->whereNotNull('doctor_coupon_id')
            ->whereColumn('used', '<', 'included')
            ->whereHas('doctorCoupon', function ($query) use ($serviceIds) {
                $query->where('doctor_id', $this->appointment->doctor_id)
                    ->whereHas('coupon', function ($couponQuery) use ($serviceIds) {
                        $couponQuery->whereNull('service_id')
                            ->orWhereIn('service_id', $serviceIds);
                    });
            })
            ->first();

        $this->hasCouponAvailable = $this->availableCouponBenefit !== null;
    }

    private function calculateTotals(): void
    {
        $subtotal = $this->parseMoney($this->subtotal);
        $doctor = $this->appointment->doctor;

        if (! $doctor) {
            $this->couponDiscountValue = 0;
            $this->user_payment = $this->formatMoney($subtotal);
            $this->commision = '0.00';
            $this->total = $this->formatMoney($subtotal);
            return;
        }

        $memberDiscount = round($subtotal * ($doctor->discount / 100), 2);
        $doctorCommission = $doctor->commission / 100;
        $this->couponDiscountValue = 0;

        if ($this->useCoupon && $this->availableCouponBenefit) {
            $coupon = $this->availableCouponBenefit->doctorCoupon->coupon;

            if ($coupon->type === 'Amount') {
                $this->couponDiscountValue = (float) $coupon->value;
            } elseif ($coupon->type === 'Percentage') {
                $this->couponDiscountValue = round($subtotal * ($coupon->value / 100), 2);
            }
        }

        $effectiveSubtotal = $this->useCoupon
            ? max(0, $subtotal - $this->couponDiscountValue)
            : max(0, $subtotal - $memberDiscount);

        $commission = $subtotal * $doctorCommission;

        $this->user_payment = $this->formatMoney($effectiveSubtotal);

        if ($this->useCoupon) {
            $providerTotal = $subtotal - $memberDiscount - $commission;
            $this->total = $this->formatMoney($providerTotal);
            $this->commision = $this->formatMoney($effectiveSubtotal - $providerTotal);
            return;
        }

        $this->commision = $this->formatMoney($commission);
        $this->total = $this->formatMoney($subtotal - $memberDiscount - $commission);
    }

    private function parseMoney(null|string|float|int $value): float
    {
        return (float) str_replace(',', '', (string) ($value ?? 0));
    }

    private function formatMoney(null|string|float|int $value): string
    {
        return number_format($this->parseMoney($value), 2, '.', '');
    }
}