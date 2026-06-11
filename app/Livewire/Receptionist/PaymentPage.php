<?php

namespace App\Livewire\Receptionist;

use App\Models\Appointment;
use App\Models\Parameter;
use App\Models\PolicyService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class PaymentPage extends Component
{
    use WithFileUploads;

    public Appointment $appointment;
    public string $subtotal = '0.00';
    public string $user_payment = '0.00';
    public string $commision = '0.00';
    public string $total = '0.00';
    public string $payment_method = '';
    public ?string $payment_reference = null;
    public $payment_attachment = null;
    public bool $hasCouponAvailable = false;
    public bool $paymentSaved = false;
    public string $paymentSuccessMessage = '';
    public Collection $availableCoupons;
    public null|int|string $selectedCouponId = null;
    public float $couponDiscountValue = 0;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.receptionist.payment-page');
    }

    public function mount(Appointment $appointment): void
    {
        $this->availableCoupons = collect();

        $this->appointment = $appointment->load(['user.policy', 'doctor.user', 'doctor.specialty', 'services.service:id,name']);

        if (! Auth::user()->staffDoctors()->whereKey($this->appointment->doctor_id)->exists()) {
            throw new AuthorizationException();
        }

        $this->subtotal = $this->formatMoney($this->appointment->subtotal);
        $this->user_payment = $this->formatMoney($this->appointment->user_payment);
        $this->commision = $this->formatMoney($this->appointment->commission);
        $this->total = $this->formatMoney($this->appointment->total);
        $this->payment_method = (string) ($this->appointment->payment_method ?? '');
        $this->payment_reference = $this->appointment->payment_reference;

        $this->checkCouponAvailability();
        $this->calculateTotals();
    }

    public function updatedSubtotal(): void
    {
        $this->calculateTotals();
    }

    public function updatedSelectedCouponId(): void
    {
        $this->calculateTotals();
    }

    public function save(): void
    {
        $allServicesCovered = $this->allCompletedServicesCovered();

        if ($this->parseMoney($this->subtotal) <= 0 && ! $allServicesCovered) {
            $this->addError('subtotal', 'Ingrese un monto valido.');
            return;
        }

        $this->dispatch('open-payment-modal');
    }

    public function confirmPayment()
    {
        $this->validatePaymentFields();

        $subtotal = $this->parseMoney($this->subtotal);
        $allServicesCovered = $this->allCompletedServicesCovered();

        if ($subtotal <= 0 && ! $allServicesCovered) {
            $this->addError('subtotal', 'Ingrese un monto valido.');
            return;
        }

        $this->calculateTotals();

        if ($this->selectedCouponId && (float) $this->appointment->coupon_discount <= 0) {
            PolicyService::whereKey($this->selectedCouponId)->increment('used');
        }

        $attachmentPath = $this->appointment->payment_attachment_path;
        $attachmentName = $this->appointment->payment_attachment_name;

        if ($this->payment_attachment) {
            $attachmentPath = $this->payment_attachment->store('payment-attachments');
            $attachmentName = $this->payment_attachment->getClientOriginalName();
        }

        $this->appointment->update([
            'subtotal' => $subtotal,
            'coupon_discount' => $this->couponDiscountValue,
            'user_payment' => $this->parseMoney($this->user_payment),
            'commission' => $this->parseMoney($this->commision),
            'total' => $this->parseMoney($this->total),
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'payment_attachment_path' => $attachmentPath,
            'payment_attachment_name' => $attachmentName,
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

        $this->dispatch(
            'payment-completed',
            ticketUrl: route('receptionist.payment.ticket', ['appointment' => $this->appointment->id]),
            redirectUrl: route('receptionist.appointments', ['tab' => 'paid'])
        );

        $this->reset('payment_attachment');
    }

    private function validatePaymentFields(): void
    {
        $validated = $this->validate([
            'payment_method' => ['required', Rule::in(['CS', 'CC', 'DC', 'TR', 'SI'])],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'payment_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            'payment_method.required' => 'Seleccione un metodo de pago.',
            'payment_method.in' => 'Seleccione un metodo de pago valido.',
            'payment_attachment.mimes' => 'El comprobante debe ser PDF, JPG o PNG.',
            'payment_attachment.max' => 'El comprobante no debe superar 2MB.',
        ]);

        if (in_array($validated['payment_method'], ['CC', 'DC', 'TR'], true) && blank($validated['payment_reference'])) {
            throw ValidationException::withMessages([
                'payment_reference' => 'La referencia es obligatoria para este metodo de pago.',
            ]);
        }
    }

    public function checkCouponAvailability(): void
    {
        $this->hasCouponAvailable = false;
        $this->availableCoupons = collect();

        $policy = $this->appointment->user->policy;

        if (! $policy) {
            $this->selectedCouponId = null;
            return;
        }

        $policyId = $policy->type === 'Member' ? $policy->parent_policy_id : $policy->id;
        $serviceIds = $this->appointment->services->pluck('service_id')->toArray();
        $subtotal = $this->parseMoney($this->subtotal);

        $this->availableCoupons = PolicyService::with('coupon')
            ->where('policy_id', $policyId)
            ->whereNotNull('coupon_id')
            ->whereColumn('used', '<', 'included')
            ->whereHas('coupon', function ($query) use ($serviceIds, $subtotal) {
                $query->whereHas('doctors', function ($dq) {
                    $dq->where('doctor_id', $this->appointment->doctor_id);
                })
                ->where(function ($serviceQuery) use ($serviceIds) {
                    $serviceQuery->whereNull('service_id')
                        ->orWhereIn('service_id', $serviceIds);
                })
                ->where(function ($limitMinQuery) use ($subtotal) {
                    $limitMinQuery->where('limit_min', '<=', 0)
                        ->orWhere('limit_min', '<=', $subtotal);
                })->where(function ($limitMaxQuery) use ($subtotal) {
                    $limitMaxQuery->where('limit_max', '<=', 0)
                        ->orWhere('limit_max', '>', $subtotal);
                });
            })
            ->get();

        if ($this->availableCoupons->isNotEmpty()) {
            $this->hasCouponAvailable = true;

            if ($this->selectedCouponId && ! $this->availableCoupons->contains('id', $this->selectedCouponId)) {
                $this->selectedCouponId = null;
            }
        } else {
            $this->selectedCouponId = null;
        }
    }

    private function calculateTotals(): void
    {
        // Check if an included MG consultation is being performed
        $mgParam = Parameter::where('type', 'MG')->where('key', 'Consulta')->first();
        $mgServiceId = $mgParam ? (int) $mgParam->value : null;

        $isMGIncluded = false;
        if ($mgServiceId) {
            $isMGIncluded = $this->appointment->services->where('service_id', $mgServiceId)
                ->where('covered', 1)
                ->where('status', 'Completed') // In PaymentPage, services are already marked Completed by doctor
                ->isNotEmpty();
        }

        // Auto-set subtotal if it's an included MG consultation and current subtotal is empty
        if ($isMGIncluded && (empty($this->subtotal) || $this->parseMoney($this->subtotal) == 0)) {
            $costoParam = Parameter::where('type', 'MG')->where('key', 'Costo')->first();
            if ($costoParam) {
                $this->subtotal = $this->formatMoney($costoParam->value);
            }
        }

        $this->checkCouponAvailability();

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
        $selectedBenefit = null;

        if ($this->selectedCouponId && $this->availableCoupons->isNotEmpty()) {
            $selectedBenefit = $this->availableCoupons->firstWhere('id', $this->selectedCouponId);

            if ($selectedBenefit) {
                $coupon = $selectedBenefit->coupon;

                if ($coupon->type === 'Amount') {
                    $this->couponDiscountValue = (float) $coupon->value;
                } elseif ($coupon->type === 'Percentage') {
                    $this->couponDiscountValue = round($subtotal * ($coupon->value / 100), 2);
                }
            }
        }

        if ($isMGIncluded) {
            $effectiveSubtotal = 0;
        } else {
            $effectiveSubtotal = $selectedBenefit
                ? max(0, $subtotal - $this->couponDiscountValue)
                : max(0, $subtotal - $memberDiscount);
        }

        $commission = $subtotal * $doctorCommission;

        $this->user_payment = $this->formatMoney($effectiveSubtotal);

        if ($isMGIncluded) {
            $this->total = $this->formatMoney($subtotal - $memberDiscount - $commission);
            $this->commision = $this->formatMoney(0);
        } elseif ($selectedBenefit) {
            $providerTotal = $subtotal - $memberDiscount - $commission;
            $this->total = $this->formatMoney($providerTotal);
            $this->commision = $this->formatMoney($effectiveSubtotal - $providerTotal);
        } else {
            $this->commision = $this->formatMoney($commission);
            $this->total = $this->formatMoney($subtotal - $memberDiscount - $commission);
        }
    }

    private function parseMoney(null|string|float|int $value): float
    {
        return (float) str_replace(',', '', (string) ($value ?? 0));
    }

    private function allCompletedServicesCovered(): bool
    {
        $completedServices = $this->appointment->services
            ->where('status', 'Completed');

        return $completedServices->isNotEmpty()
            && $completedServices->every(fn ($service) => (bool) $service->covered);
    }

    private function formatMoney(null|string|float|int $value): string
    {
        return number_format($this->parseMoney($value), 2, '.', ',');
    }
}