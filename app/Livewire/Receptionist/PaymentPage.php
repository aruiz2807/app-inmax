<?php

namespace App\Livewire\Receptionist;

use App\Enums\AppointmentStatus;
use App\Enums\DoctorType;
use App\Livewire\Concerns\WithAppointmentServiceAdditions;
use App\Models\Appointment;
use App\Models\AppointmentNote;
use App\Models\AppointmentService;
use App\Models\DoctorService;
use App\Models\Parameter;
use App\Models\PolicyService;
use App\Models\Service;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class PaymentPage extends Component
{
    use WithAppointmentServiceAdditions;
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

    public array $selectedCouponIds = [];

    public float $couponDiscountValue = 0;

    public bool $canManageServices = false;

    public bool $canCapturePrices = false;

    public bool $requiresResultsConfirmation = false;

    public array $servicesToComplete = [];

    public array $servicePrices = [];

    /** Services added by the receptionist that are not stored until the payment is confirmed, keyed by temporary negative id. */
    public array $pendingServices = [];

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
            throw new AuthorizationException;
        }

        $this->subtotal = $this->formatMoney($this->appointment->subtotal);
        $this->user_payment = $this->formatMoney($this->appointment->user_payment);
        $this->commision = $this->formatMoney($this->appointment->commission);
        $this->total = $this->formatMoney($this->appointment->total);
        $this->payment_method = (string) ($this->appointment->payment_method ?? '');
        $this->payment_reference = $this->appointment->payment_reference;

        $this->canManageServices = $this->appointment->status === AppointmentStatus::BOOKED;
        $this->canCapturePrices = is_null($this->appointment->user_payment)
            && in_array($this->appointment->status, [AppointmentStatus::COMPLETED, AppointmentStatus::RESULTS_PENDING], true);

        // Los proveedores generan estudios cuyos resultados pueden subirse despues; los doctores no.
        $this->requiresResultsConfirmation = ($this->canManageServices || $this->canCapturePrices)
            && $this->appointment->doctor?->type === DoctorType::Provider;

        if ($this->canManageServices || $this->canCapturePrices) {
            foreach ($this->appointment->services as $service) {
                $this->servicesToComplete[$service->id] = ! $this->canManageServices
                    && $service->status === AppointmentStatus::COMPLETED->value;
                $this->servicePrices[$service->id] = $this->defaultServicePrice($service);
            }

            $this->syncSubtotalWithServices();
        }

        $this->checkCouponAvailability();
        $this->calculateTotals();
    }

    /**
     * Prefill a service's price with the previously captured amount, or fall back to the doctor/service default price.
     */
    private function defaultServicePrice(AppointmentService $service): string
    {
        if ($service->subtotal !== null) {
            return number_format((float) $service->subtotal, 2, '.', '');
        }

        if (! $service->service_id) {
            return '';
        }

        $price = $this->getServicePrice($service->service_id);

        return $price > 0 ? number_format($price, 2, '.', '') : '';
    }

    public function updated($name, $value): void
    {
        if (str_starts_with($name, 'servicesToComplete.') || str_starts_with($name, 'servicePrices.')) {
            if (str_starts_with($name, 'servicesToComplete.') && ! $value) {
                $appointmentServiceId = (int) substr($name, strlen('servicesToComplete.'));
                $service = $this->appointment->services->firstWhere('id', $appointmentServiceId);

                if ($service?->service_id) {
                    $this->selectedCouponIds = array_values(array_filter(
                        $this->selectedCouponIds,
                        function ($selectedId) use ($service) {
                            $benefit = $this->availableCoupons->firstWhere('id', (int) $selectedId);

                            return (int) $benefit?->coupon?->service_id !== (int) $service->service_id;
                        }
                    ));
                }
            }

            $this->syncSubtotalWithServices();
            $this->checkCouponAvailability();

            if (str_starts_with($name, 'servicesToComplete.') && $value) {
                $appointmentServiceId = (int) substr($name, strlen('servicesToComplete.'));
                $service = $this->appointment->services->firstWhere('id', $appointmentServiceId);

                if ($service?->service_id) {
                    $this->selectFirstCouponForService((int) $service->service_id);
                }
            }

            $this->calculateTotals();
        } elseif (str_starts_with($name, 'selectedCouponIds')) {
            $this->normalizeSelectedCouponIds();
            $this->calculateTotals();
        }
    }

    /**
     * Livewire re-queries the appointment on every request, so pending (unsaved) services must be re-attached.
     */
    public function hydrate(): void
    {
        $this->attachPendingServices();
    }

    private function attachPendingServices(): void
    {
        if (empty($this->pendingServices)) {
            return;
        }

        $persisted = $this->appointment->services->filter(fn ($service) => $service->exists);

        $pending = collect($this->pendingServices)->map(function (array $data, $tempId) {
            $service = (new AppointmentService)->forceFill([
                'id' => (int) $tempId,
                'appointment_id' => $this->appointment->id,
                'service_id' => $data['service_id'],
                'unregistered_service' => $data['unregistered_service'],
                'covered' => $data['covered'],
                'status' => AppointmentStatus::COMPLETED->value,
            ]);
            $service->setRelation('appointment', $this->appointment);

            return $service;
        })->values();

        $this->appointment->setRelation('services', $persisted->concat($pending)->values());
    }

    /**
     * Keep a newly selected service in memory; it is only stored once the payment is confirmed.
     */
    public function registerNewService(array $serviceData): void
    {
        if (! $this->canManageServices) {
            return;
        }

        $tempId = min(array_merge([0], array_keys($this->pendingServices))) - 1;

        $this->pendingServices[$tempId] = [
            'service_id' => $serviceData['id'] ?? null,
            'unregistered_service' => $serviceData['unregistered_service'] ?? null,
            'covered' => ! empty($serviceData['included']) ? 1 : 0,
        ];

        $this->attachPendingServices();

        $pendingService = $this->appointment->services->firstWhere('id', $tempId);

        $this->servicesToComplete[$tempId] = true;
        $this->servicePrices[$tempId] = $this->defaultServicePrice($pendingService);
    }

    /**
     * Reload the appointment services collection and recalculate totals.
     */
    public function refreshAppointmentServices(): void
    {
        $this->appointment->load('services.service:id,name');
        $this->attachPendingServices();
        $this->syncSubtotalWithServices();
        $this->calculateTotals();
    }

    /**
     * Store the pending services and remap their temporary ids to the persisted ones.
     */
    private function persistPendingServices(): void
    {
        foreach ($this->pendingServices as $tempId => $data) {
            $created = AppointmentService::create([
                'appointment_id' => $this->appointment->id,
                'service_id' => $data['service_id'],
                'unregistered_service' => $data['unregistered_service'],
                'covered' => $data['covered'],
                'status' => AppointmentStatus::COMPLETED->value,
            ]);

            $this->servicesToComplete[$created->id] = $this->servicesToComplete[$tempId] ?? true;
            $this->servicePrices[$created->id] = $this->servicePrices[$tempId] ?? '';
            unset($this->servicesToComplete[$tempId], $this->servicePrices[$tempId]);
        }

        $this->pendingServices = [];
        $this->appointment->load('services.service:id,name');
    }

    /**
     * Sum the doctor's price for every service marked as completed and use it as the account subtotal.
     */
    private function syncSubtotalWithServices(): void
    {
        if (! $this->canManageServices && ! $this->canCapturePrices) {
            return;
        }

        $this->subtotal = $this->formatMoney($this->calculateServicesSubtotal());
    }

    /**
     * Sum the price captured per service for every service marked as completed.
     */
    private function calculateServicesSubtotal(): float
    {
        return $this->appointment->services->reduce(function ($carry, $service) {
            if (! $this->isServiceCompleted($service)) {
                return $carry;
            }

            $price = $this->parseMoney($this->servicePrices[$service->id] ?? 0);

            return $carry + $price;
        }, 0.0);
    }

    private function getDoctorPrices(array $serviceIds)
    {
        return DoctorService::where('doctor_id', $this->appointment->doctor_id)
            ->whereIn('service_id', $serviceIds)
            ->pluck('price', 'service_id');
    }

    /**
     * Get the price of a single service, falling back to the service's default price.
     */
    private function getServicePrice(int $serviceId): float
    {
        $price = $this->getDoctorPrices([$serviceId])[$serviceId]
            ?? Service::where('id', $serviceId)->value('price')
            ?? 0;

        return (float) $price;
    }

    private function getCouponServiceAmount(int $serviceId): float
    {
        $service = $this->appointment->services->firstWhere('service_id', $serviceId);

        if (! $service || ! $this->isServiceCompleted($service)) {
            return 0;
        }

        if ($service->subtotal !== null) {
            return (float) $service->subtotal;
        }

        $capturedPrice = $this->servicePrices[$service->id] ?? null;

        return $capturedPrice !== null && $capturedPrice !== ''
            ? $this->parseMoney($capturedPrice)
            : $this->getServicePrice($serviceId);
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
        if (($this->canManageServices || $this->canCapturePrices) && ! collect($this->servicesToComplete)->contains(true)) {
            $this->addError('servicesToComplete', 'Debe marcar al menos un servicio como realizado.');

            return;
        }

        $allServicesCovered = $this->allCompletedServicesCovered();

        if ($this->parseMoney($this->subtotal) <= 0 && ! $allServicesCovered) {
            $this->addError('subtotal', 'Ingrese un monto valido.');

            return;
        }

        $this->validatePaymentFields();

        $this->dispatch('open-payment-modal');
    }

    public function confirmPayment(?bool $willUploadResultsLater = null)
    {
        $isClosingAccount = $this->canManageServices || $this->canCapturePrices;

        if ($isClosingAccount && ! collect($this->servicesToComplete)->contains(true)) {
            $this->addError('servicesToComplete', 'Debe marcar al menos un servicio como realizado.');

            return;
        }

        // Si el proveedor debe confirmar la carga de resultados, no se procede hasta obtener la respuesta.
        if ($isClosingAccount && $this->requiresResultsConfirmation && $willUploadResultsLater === null) {
            return;
        }

        $this->validatePaymentFields();

        $subtotal = $this->parseMoney($this->subtotal);
        $allServicesCovered = $this->allCompletedServicesCovered();

        if ($subtotal <= 0 && ! $allServicesCovered) {
            $this->addError('subtotal', 'Ingrese un monto valido.');

            return;
        }

        if ($isClosingAccount) {
            $this->persistPendingServices();
            $this->persistServicesCompletion();
        }

        $this->calculateTotals();

        if ($isClosingAccount) {
            $this->redeemPolicyServices();
        }

        if ((float) $this->appointment->coupon_discount <= 0) {
            foreach ($this->selectedCouponBenefits() as $selectedBenefit) {
                $selectedBenefit->increment('used');
                $this->storeRedeemedCoupon($selectedBenefit->coupon_id, $selectedBenefit->coupon?->service_id);
            }
        }

        $attachmentPath = $this->appointment->payment_attachment_path;
        $attachmentName = $this->appointment->payment_attachment_name;

        if ($this->payment_attachment) {
            $attachmentPath = $this->payment_attachment->store('payment-attachments');
            $attachmentName = $this->payment_attachment->getClientOriginalName();
        }

        $updateData = [
            'subtotal' => $subtotal,
            'coupon_discount' => $this->couponDiscountValue,
            'user_payment' => $this->parseMoney($this->user_payment),
            'commission' => $this->parseMoney($this->commision),
            'total' => $this->parseMoney($this->total),
            'payment_method' => $this->payment_method,
            'payment_reference' => $this->payment_reference,
            'payment_attachment_path' => $attachmentPath,
            'payment_attachment_name' => $attachmentName,
        ];

        // Una cita Booked pasa a Completed al cerrar su cuenta; una atendida pendiente de liquidar queda Completed,
        // salvo que el proveedor indique que subira los resultados despues, en cuyo caso queda ResultsPending.
        if ($isClosingAccount) {
            $updateData['status'] = ($this->requiresResultsConfirmation && $willUploadResultsLater === true)
                ? AppointmentStatus::RESULTS_PENDING
                : AppointmentStatus::COMPLETED;
        }

        $this->appointment->update($updateData);
        $this->persistServiceFinancials($subtotal);

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
            ticketUrl: route('receptionist.payment.ticket', ['appointment' => $this->appointment->id, 'type' => 'member']),
            redirectUrl: route('receptionist.appointments', ['tab' => 'paid'])
        );

        $this->reset('payment_attachment');
    }

    private function storeRedeemedCoupon(?int $couponId, ?int $serviceId): void
    {
        if (! $couponId) {
            return;
        }

        $completedServices = $this->appointment->services->filter(
            fn ($service) => $this->isServiceCompleted($service)
        );

        if ($serviceId) {
            $completedServices = $completedServices->where('service_id', $serviceId);
        }

        foreach ($completedServices as $service) {
            $service->update(['coupon_id' => $couponId]);
        }
    }

    private function validatePaymentFields(): void
    {
        $this->validate([
            'payment_method' => ['required', Rule::in(['CS', 'CC', 'DC', 'TR', 'SI'])],
            'payment_reference' => ['required', 'string', 'max:100'],
            'payment_attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            'payment_method.required' => 'Seleccione un metodo de pago.',
            'payment_method.in' => 'Seleccione un metodo de pago valido.',
            'payment_reference.required' => 'Ingrese la referencia de pago.',
            'payment_attachment.mimes' => 'El comprobante debe ser PDF, JPG o PNG.',
            'payment_attachment.max' => 'El comprobante no debe superar 2MB.',
        ]);
    }

    public function checkCouponAvailability(): void
    {
        $this->hasCouponAvailable = false;
        $this->availableCoupons = collect();

        $policy = $this->appointment->user->policy;

        if (! $policy) {
            $this->selectedCouponIds = [];

            return;
        }

        $policyId = $policy->type === 'Member' ? $policy->parent_policy_id : $policy->id;
        $serviceIds = $this->appointment->services
            ->filter(fn ($service) => $this->isServiceCompleted($service))
            ->pluck('service_id')
            ->filter()
            ->unique()
            ->values()
            ->all();
        $subtotal = $this->parseMoney($this->subtotal);

        if ($serviceIds === []) {
            $this->selectedCouponIds = [];

            return;
        }

        $this->availableCoupons = PolicyService::with('coupon')
            ->where('policy_id', $policyId)
            ->whereNotNull('coupon_id')
            ->whereColumn('used', '<', 'included')
            ->orderBy('id')
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
            ->get()
            ->filter(function ($benefit) {
                $serviceId = $benefit->coupon->service_id;
                if (! $serviceId) {
                    return true;
                }

                $service = $this->appointment->services->firstWhere('service_id', $serviceId);

                return $service && $this->isServiceCompleted($service);
            })
            ->values();

        if ($this->availableCoupons->isNotEmpty()) {
            $this->hasCouponAvailable = true;
        } else {
            $this->selectedCouponIds = [];
        }

        $this->normalizeSelectedCouponIds();
    }

    private function selectFirstCouponForService(int $serviceId): void
    {
        $benefit = $this->availableCoupons->first(
            fn ($benefit) => (int) $benefit->coupon?->service_id === $serviceId
        );

        if (! $benefit) {
            return;
        }

        $this->selectedCouponIds = array_values(array_filter(
            $this->selectedCouponIds,
            function ($selectedId) use ($serviceId) {
                $selectedBenefit = $this->availableCoupons->firstWhere('id', (int) $selectedId);
                $selectedServiceId = $selectedBenefit?->coupon?->service_id;

                return $selectedBenefit
                    && $selectedServiceId
                    && (int) $selectedServiceId !== $serviceId;
            }
        ));
        $this->selectedCouponIds[] = $benefit->id;
        $this->normalizeSelectedCouponIds();
    }

    private function normalizeSelectedCouponIds(): void
    {
        $selectedIds = [];
        $selectedServiceIds = [];

        foreach ($this->selectedCouponIds as $selectedId) {
            $benefit = $this->availableCoupons->firstWhere('id', (int) $selectedId);
            $serviceId = $benefit?->coupon?->service_id;

            if (! $benefit) {
                continue;
            }

            if (! $serviceId) {
                if ($selectedIds === []) {
                    $selectedIds[] = $benefit->id;
                }

                break;
            }

            if (! $this->hasCompletedCouponService($serviceId)) {
                continue;
            }

            if (! in_array((int) $serviceId, $selectedServiceIds, true)) {
                $selectedIds[] = $benefit->id;
                $selectedServiceIds[] = (int) $serviceId;
            }
        }

        $this->selectedCouponIds = $selectedIds;
    }

    private function selectedCouponBenefits(): Collection
    {
        $selectedIds = array_map('intval', $this->selectedCouponIds);

        return $this->availableCoupons
            ->filter(function ($benefit) use ($selectedIds) {
                if (! in_array((int) $benefit->id, $selectedIds, true)) {
                    return false;
                }

                $serviceId = $benefit->coupon->service_id;
                if (! $serviceId) {
                    return true;
                }

                return $this->hasCompletedCouponService($serviceId);
            })
            ->values();
    }

    public function canSelectCoupon(int $benefitId): bool
    {
        $benefit = $this->availableCoupons->firstWhere('id', $benefitId);
        if (! $benefit || in_array($benefitId, $this->selectedCouponIds)) {
            return (bool) $benefit;
        }

        $serviceId = $benefit->coupon->service_id;
        if ($serviceId) {
            if (! $this->hasCompletedCouponService($serviceId)) {
                return false;
            }
        }

        foreach ($this->selectedCouponBenefits() as $selectedBenefit) {
            $selectedServiceId = $selectedBenefit->coupon->service_id;

            if (! $serviceId || ! $selectedServiceId || (int) $serviceId === (int) $selectedServiceId) {
                return false;
            }
        }

        return true;
    }

    private function hasCompletedCouponService(int $serviceId): bool
    {
        return $this->appointment->services->contains(
            fn ($service) => (int) $service->service_id === $serviceId
                && $this->isServiceCompleted($service)
        );
    }

    private function calculateTotals(): void
    {
        // Verifica si se esta realizando una consulta MG incluida
        $mgParam = Parameter::where('type', 'MG')->where('key', 'Consulta')->first();
        $mgServiceId = $mgParam ? (int) $mgParam->value : null;

        $isMGIncluded = false;
        if ($mgServiceId) {
            $isMGIncluded = $this->appointment->services->where('service_id', $mgServiceId)
                ->where('covered', 1)
                ->filter(fn ($service) => $this->isServiceCompleted($service))
                ->isNotEmpty();
        }

        $costoParam = Parameter::where('type', 'MG')->where('key', 'Costo')->first();
        if ($costoParam && $isMGIncluded) {
            $this->subtotal = number_format($costoParam->value, 2);
        }

        $this->checkCouponAvailability();

        $subtotal = $this->parseMoney($this->subtotal);
        // Services marked as "incluido" are covered by the policy, so the patient owes nothing for them.
        $coveredAmount = $this->calculateCoveredServicesAmount();
        $payableSubtotal = max(0, $subtotal - $coveredAmount);
        $doctor = $this->appointment->doctor;

        if (! $doctor) {
            $this->couponDiscountValue = 0;
            $this->user_payment = $this->formatMoney($payableSubtotal);
            $this->commision = '0.00';
            $this->total = $this->formatMoney($subtotal);

            return;
        }

        $doctorCommission = $doctor->commission / 100;
        $this->normalizeSelectedCouponIds();
        $selectedBenefits = $this->selectedCouponBenefits();
        $this->couponDiscountValue = 0;
        $couponCoverageTotal = 0;

        foreach ($selectedBenefits as $benefit) {
            $coupon = $benefit->coupon;
            $couponServiceAmount = $coupon->service_id
                ? $this->getCouponServiceAmount($coupon->service_id)
                : 0;
            $couponDiscount = 0;

            if ($coupon->type === 'Amount') {
                $couponDiscount = (float) $coupon->value;
            } elseif ($coupon->type === 'Percentage') {
                $couponBase = $coupon->service_id ? $couponServiceAmount : $subtotal;
                $couponDiscount = round($couponBase * ($coupon->value / 100), 2);
            } elseif ($coupon->type === 'Service') {
                $couponDiscount = $couponServiceAmount;
            }

            $this->couponDiscountValue += $couponDiscount;
            $couponCoverage = round($coupon->inmax_coverage_percent / 100, 2);
            $coverageBase = $coupon->service_id ? $couponServiceAmount : $subtotal;
            $couponCoverageTotal += round($coverageBase * $couponCoverage, 2);
        }

        $memberDiscountBase = max(0, $payableSubtotal - $this->couponDiscountValue);
        $memberDiscount = round($memberDiscountBase * ($doctor->discount / 100), 2);

        if ($isMGIncluded) {
            $effectiveSubtotal = 0;
        } else {
            $effectiveSubtotal = $selectedBenefits->isNotEmpty()
                ? max(0, $payableSubtotal - $this->couponDiscountValue - $memberDiscount)
                : max(0, $payableSubtotal - $memberDiscount);
        }

        $commission = $effectiveSubtotal * $doctorCommission;

        $this->user_payment = $this->formatMoney($effectiveSubtotal);

        if ($isMGIncluded) {
            $this->total = $this->formatMoney(200);
            $this->commision = $this->formatMoney(0);
        } elseif ($selectedBenefits->isNotEmpty()) {
            $providerTotal = $subtotal - $memberDiscount - $commission;
            $this->total = $this->formatMoney($providerTotal);
            $this->commision = $this->formatMoney($effectiveSubtotal - $providerTotal);

            if (
                $selectedBenefits->count() > 1
                || $selectedBenefits->contains(fn ($benefit) => $benefit->coupon->type === 'Service')
                || $couponCoverageTotal > 0
            ) {
                $remainingCommission = $effectiveSubtotal * $doctorCommission;

                $this->commision = $this->formatMoney($remainingCommission - $couponCoverageTotal);
                $this->total = $this->formatMoney(
                    max(0, $effectiveSubtotal - $remainingCommission) + $couponCoverageTotal
                );
            }
        } else {
            $this->commision = $this->formatMoney($commission);
            $this->total = $this->formatMoney($subtotal - $memberDiscount - $commission);
        }
    }

    private function parseMoney(null|string|float|int $value): float
    {
        return (float) str_replace(',', '', (string) ($value ?? 0));
    }

    /**
     * Sum the captured price of completed services covered by the policy (patient owes nothing for these).
     */
    private function calculateCoveredServicesAmount(): float
    {
        return $this->appointment->services->reduce(function ($carry, $service) {
            if (! $this->isServiceCompleted($service) || ! $service->covered) {
                return $carry;
            }

            return $carry + $this->parseMoney($this->servicePrices[$service->id] ?? 0);
        }, 0.0);
    }

    private function allCompletedServicesCovered(): bool
    {
        $completedServices = $this->appointment->services
            ->filter(fn ($service) => $this->isServiceCompleted($service));

        return $completedServices->isNotEmpty()
            && $completedServices->every(fn ($service) => (bool) $service->covered);
    }

    private function isServiceCompleted(AppointmentService $service): bool
    {
        if ($this->canManageServices || $this->canCapturePrices) {
            return (bool) ($this->servicesToComplete[$service->id] ?? false);
        }

        return $service->status === AppointmentStatus::COMPLETED->value;
    }

    private function persistServicesCompletion(): void
    {
        $appointmentServices = $this->appointment->services->keyBy('id');

        foreach ($this->servicesToComplete as $serviceId => $isDone) {
            if (! isset($appointmentServices[$serviceId])) {
                continue;
            }

            $appointmentServices[$serviceId]->update([
                'status' => $isDone ? AppointmentStatus::COMPLETED->value : AppointmentStatus::CANCELLED->value,
            ]);
        }

        // El ticket requiere una nota; las citas Booked aun no tienen una
        AppointmentNote::firstOrCreate(['appointment_id' => $this->appointment->id]);

        $this->appointment->load('services.service:id,name', 'note');
    }

    /**
     * Consume the policy's included-service benefits for every completed service (mirrors DRNotesPage::redeem()).
     */
    private function redeemPolicyServices(): void
    {
        $policy = $this->appointment->user->policy;

        if (! $policy) {
            return;
        }

        $policyId = $policy->type === 'Member' ? $policy->parent_policy_id : $policy->id;

        foreach ($this->appointment->services as $service) {
            if (! $this->isServiceCompleted($service) || ! $service->service_id) {
                continue;
            }

            $benefit = PolicyService::where('policy_id', $policyId)
                ->where('service_id', $service->service_id)
                ->orderByRaw('used < included DESC') // Prioritize ones with remaining space
                ->first();

            if ($benefit) {
                $service->update(['coupon_id' => $benefit->coupon_id]);

                if ($benefit->used < $benefit->included) {
                    $benefit->increment('used');
                } else {
                    $benefit->increment('extra');
                }
            }
        }
    }

    /**
     * Distribute the appointment-level financial breakdown across every completed service,
     * proportionally to the price captured for each one.
     */
    private function persistServiceFinancials(float $totalSubtotal): void
    {
        if (! $this->canManageServices && ! $this->canCapturePrices) {
            return;
        }

        $totalCoupon = $this->couponDiscountValue;
        $totalUserPayment = $this->parseMoney($this->user_payment);
        $totalCommission = $this->parseMoney($this->commision);
        $totalAmount = $this->parseMoney($this->total);
        $selectedBenefits = $this->selectedCouponBenefits();
        $serviceCouponBenefits = $selectedBenefits
            ->filter(fn ($benefit) => $benefit->coupon->service_id)
            ->keyBy(fn ($benefit) => (int) $benefit->coupon->service_id);
        $serviceCouponIds = $serviceCouponBenefits->keys()->all();
        $couponCoverageTotal = $selectedBenefits->sum(function ($benefit) use ($totalSubtotal) {
            $coupon = $benefit->coupon;
            $serviceAmount = $coupon->service_id
                ? $this->getCouponServiceAmount($coupon->service_id)
                : $totalSubtotal;

            return round($serviceAmount * round((float) $coupon->inmax_coverage_percent / 100, 2), 2);
        });
        $coveredAmount = $this->appointment->services->reduce(function ($carry, $service) use ($serviceCouponIds) {
            if (! $this->isServiceCompleted($service) || ! $service->covered || in_array((int) $service->service_id, $serviceCouponIds, true)) {
                return $carry;
            }

            return $carry + $this->parseMoney($this->servicePrices[$service->id] ?? 0);
        }, 0.0);
        $serviceCouponDiscountTotal = $serviceCouponBenefits->sum(function ($benefit) {
            $coupon = $benefit->coupon;
            $serviceAmount = $this->getCouponServiceAmount($coupon->service_id);

            return match ($coupon->type) {
                'Service' => $serviceAmount,
                'Percentage' => round($serviceAmount * ((float) $coupon->value / 100), 2),
                'Amount' => (float) $coupon->value,
                default => 0,
            };
        });
        $remainingSubtotal = max(0, $totalSubtotal - $serviceCouponDiscountTotal - $coveredAmount);
        $totalAmountForShare = $totalAmount - $couponCoverageTotal - $coveredAmount;

        foreach ($this->appointment->services as $service) {
            if (! $this->isServiceCompleted($service)) {
                continue;
            }

            $price = $this->parseMoney($this->servicePrices[$service->id] ?? 0);

            $serviceCoupon = $serviceCouponBenefits->get((int) $service->service_id);
            if ($service->covered && ! $serviceCoupon) {
                $service->update([
                    'subtotal' => $price,
                    'coupon_discount' => 0,
                    'user_payment' => 0,
                    'commission' => 0,
                    'total' => $price,
                ]);

                continue;
            }

            $serviceCouponDiscount = $selectedBenefits->sum(function ($benefit) use ($service) {
                $coupon = $benefit->coupon;

                if (! $coupon->service_id || (int) $coupon->service_id !== (int) $service->service_id || $coupon->type === 'Service') {
                    return 0;
                }

                $serviceAmount = $this->getCouponServiceAmount($coupon->service_id);

                return match ($coupon->type) {
                    'Amount' => (float) $coupon->value,
                    'Percentage' => round($serviceAmount * ((float) $coupon->value / 100), 2),
                    default => 0,
                };
            });
            if ($serviceCoupon) {
                $serviceCouponDiscount = match ($serviceCoupon->coupon->type) {
                    'Service' => $price,
                    'Percentage' => round($price * ((float) $serviceCoupon->coupon->value / 100), 2),
                    'Amount' => (float) $serviceCoupon->coupon->value,
                    default => 0,
                };
            }
            $shareBase = max(0, $price - $serviceCouponDiscount);
            $share = $remainingSubtotal > 0 ? $shareBase / $remainingSubtotal : 0;
            $generalCouponDiscount = ($selectedBenefits->isEmpty()
                || $selectedBenefits->contains(fn ($benefit) => ! $benefit->coupon->service_id))
                ? $totalCoupon
                : 0;
            $coverage = $serviceCoupon
                ? round($price * round((float) $serviceCoupon->coupon->inmax_coverage_percent / 100, 2), 2)
                : 0;
            $normalCommission = round(($totalCommission + $couponCoverageTotal) * $share, 2);

            $service->update([
                'subtotal' => $price,
                'coupon_discount' => $serviceCouponDiscount + round($generalCouponDiscount * $share, 2),
                'user_payment' => round($totalUserPayment * $share, 2),
                'commission' => $normalCommission - $coverage,
                'total' => round($totalAmountForShare * $share, 2) + $coverage,
            ]);
        }
    }

    private function formatMoney(null|string|float|int $value): string
    {
        return number_format($this->parseMoney($value), 2, '.', ',');
    }
}
