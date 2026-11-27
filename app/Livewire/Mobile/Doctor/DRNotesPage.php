<?php

namespace App\Livewire\Mobile\Doctor;

use App\Enums\AppointmentStatus;
use App\Enums\DoctorType;
use App\Livewire\Concerns\WithAppointmentServiceAdditions;
use App\Livewire\Forms\DoctorNotesForm;
use App\Models\Appointment;
use App\Models\AppointmentPrescription;
use App\Models\AppointmentService;
use App\Models\DoctorService;
use App\Models\Medication;
use App\Models\Parameter;
use App\Models\PolicyService;
use App\Models\Service;
use App\Services\Appointments\AppointmentCompletedNotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class DRNotesPage extends Component
{
    use WithAppointmentServiceAdditions;
    use WithFileUploads;

    public DoctorNotesForm $form;

    public $appointment;

    public $services;

    public $subtotal;

    public $user_payment;

    public $commision;

    public $total;

    public $user;

    public bool $hasReceptionistAssigned = false;

    public bool $isMobileDevice = true;

    // Coupon logic
    public bool $hasCouponAvailable = false;

    public $availableCoupons = [];

    public array $selectedCouponIds = [];

    public float $couponDiscountValue = 0;

    // Medication selection
    public $searchTerm = '';

    public $prescriptions = [];

    public $medicationId = null;

    public $quantity = 1;

    public $dose = '';

    public $frequency = '';

    public $duration = '';

    public array $missingAttachmentServiceNames = [];

    public bool $isEditing = false;

    public function render()
    {
        $view = $this->isMobileDevice
            ? 'livewire.mobile.doctor.notes-page'
            : 'livewire.doctor.notes-page';

        $layout = $this->isMobileDevice ? 'layouts.mobile' : 'layouts.app';

        return view($view)->layout($layout);
    }

    public function mount($appointment)
    {
        $this->isMobileDevice = $this->detectMobileDevice();
        $desktopVersionEnabled = Parameter::where('type', 'SITE')->where('key', 'Doctor_VersionDesktop')->first()->value == 'Activa';
        ! $desktopVersionEnabled ? $this->isMobileDevice = true : '';
        $this->isEditing = request()->routeIs('doctor.notes.edit') || request()->boolean('edit');

        $this->user = Auth::user();
        $this->appointment = Appointment::with([
            'user.policy',
            'doctor.user',
            'doctor.specialty',
            'note',
            'prescriptions.medication',
            'services.service',
        ])->findOrFail($appointment);
        $this->services = AppointmentService::where('appointment_id', $this->appointment->id)->orderBy('id')->get();
        $this->form->isDoctor = $this->user->doctor->type === DoctorType::Doctor;
        $this->hasReceptionistAssigned = $this->user->doctor
            ?->staff()
            ->where('profile', 'Receptionist')
            ->exists() ?? false;

        foreach ($this->services as $service) {
            $this->form->services[$service->id] = $this->appointment->status !== AppointmentStatus::BOOKED
                && (string) $service->status === AppointmentStatus::COMPLETED->value;
            $this->form->attachments[$service->id] = null;
            $this->form->servicePrices[$service->id] = $this->defaultServicePrice($service);
        }

        if ($this->appointment->note) {
            $this->form->symptoms = $this->appointment->note->symptoms;
            $this->form->findings = $this->appointment->note->findings;
            $this->form->diagnosis = $this->appointment->note->diagnosis;
            $this->form->treatment = $this->appointment->note->treatment;
            $this->form->notes = $this->appointment->note->notes ?? '';
        }

        $this->subtotal = $this->appointment->subtotal ? number_format((float) $this->appointment->subtotal, 2, '.', ',') : null;
        $this->user_payment = $this->appointment->user_payment ? number_format((float) $this->appointment->user_payment, 2, '.', ',') : null;
        $this->commision = $this->appointment->commission ? number_format((float) $this->appointment->commission, 2, '.', ',') : null;
        $this->total = $this->appointment->total ? number_format((float) $this->appointment->total, 2, '.', ',') : null;
        $this->couponDiscountValue = (float) ($this->appointment->coupon_discount ?? 0);

        if (! $this->isEditing) {
            $this->syncSubtotalWithServices();
            $this->calculateTotals();
        }

        $this->loadPrescriptions();
        $this->checkCouponAvailability();
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

    protected function detectMobileDevice(): bool
    {
        $forcedDevice = request()->query('device');

        if ($forcedDevice === 'mobile') {
            return true;
        }

        if ($forcedDevice === 'desktop') {
            return false;
        }

        $userAgent = strtolower((string) request()->userAgent());

        return preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i', $userAgent) === 1;
    }

    #[Computed]
    public function medications()
    {
        if (empty(trim($this->searchTerm))) {
            return collect();
        }

        return Medication::where('status', 'Active')
            ->where(function ($query) {
                $query->where('name', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('trade_name', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('active_substance', 'like', '%'.$this->searchTerm.'%')
                    ->orWhere('packaging', 'like', '%'.$this->searchTerm.'%');
            })
            ->limit(25)
            ->get();
    }

    public function selectMedication($id, $name)
    {
        $this->medicationId = $id;
        $this->searchTerm = $name;
    }

    public function loadPrescriptions()
    {
        $this->prescriptions = AppointmentPrescription::with('medication')
            ->where('appointment_id', $this->appointment->id)
            ->get();
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

        $serviceIds = $this->services
            ->filter(fn ($service) => ! empty($this->form->services[$service->id]))
            ->pluck('service_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $subtotal = (float) str_replace(',', '', (string) $this->subtotal);

        if ($serviceIds === []) {
            $this->selectedCouponIds = [];

            return;
        }

        $this->availableCoupons = PolicyService::with('coupon.services')
            ->where('policy_id', $policyId)
            ->whereNotNull('coupon_id')
            ->whereColumn('used', '<', 'included')
            ->orderBy('id')
            ->whereHas('coupon', function ($query) use ($serviceIds, $subtotal) {
                $query->whereHas('doctors', function ($dq) {
                    $dq->where('doctor_id', $this->appointment->doctor_id);
                })
                    ->where(function ($serviceQuery) use ($serviceIds) {
                        $serviceQuery
                            ->whereIn('service_id', $serviceIds)
                            ->orWhereHas('services', function ($sq) use ($serviceIds) {
                                $sq->whereIn('services.id', $serviceIds);
                            })
                            ->orWhere(function ($q) {
                                $q->whereNull('service_id')
                                    ->whereDoesntHave('services');
                            });
                    })
                    ->where(function ($limitMinQuery) use ($subtotal) {
                        $limitMinQuery->where('limit_min', '<=', 0)
                            ->orWhere('limit_min', '<=', $subtotal);
                    })
                    ->where(function ($limitMaxQuery) use ($subtotal) {
                        $limitMaxQuery->where('limit_max', '<=', 0)
                            ->orWhere('limit_max', '>', $subtotal);
                    });
            })
            ->get()
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
            fn ($benefit) => in_array($serviceId, $this->couponServiceIds($benefit), true)
        );

        if (! $benefit) {
            return;
        }

        $newCovered = $this->completedCouponServiceIds($benefit);

        $this->selectedCouponIds = array_values(array_filter(
            $this->selectedCouponIds,
            function ($selectedId) use ($newCovered) {
                $selectedBenefit = $this->availableCoupons->firstWhere('id', (int) $selectedId);

                if (! $selectedBenefit || $this->isGlobalCoupon($selectedBenefit)) {
                    return false;
                }

                // Se conserva solo si no comparte servicios con el nuevo cupón
                return array_intersect(
                    $this->completedCouponServiceIds($selectedBenefit),
                    $newCovered
                ) === [];
            }
        ));

        $this->selectedCouponIds[] = $benefit->id;
        $this->normalizeSelectedCouponIds();
    }

    /** IDs de servicios que cubre el beneficio (service_id directo o pivote). */
    private function couponServiceIds($benefit): array
    {
        $coupon = $benefit?->coupon;

        return $coupon ? $coupon->serviceIds() : [];
    }

    /** Servicios que cubre el cupón Y que están realizados en la cita. */
    private function completedCouponServiceIds($benefit): array
    {
        return array_values(array_filter(
            $this->couponServiceIds($benefit),
            fn ($id) => $this->hasCompletedCouponService($id)
        ));
    }

    /** Cupón sin ningún servicio asignado: aplica a todo y es exclusivo. */
    private function isGlobalCoupon($benefit): bool
    {
        return $this->couponServiceIds($benefit) === [];
    }

    public function addMedication()
    {
        $this->validate([
            'medicationId' => 'nullable|exists:medications,id',
            'searchTerm' => 'required|string|max:250',
            'quantity' => 'required|integer|max:20',
            'dose' => 'required|string|max:50',
            'frequency' => 'required|string|max:50',
            'duration' => 'required|string|max:50',
        ]);

        AppointmentPrescription::create([
            'appointment_id' => $this->appointment->id,
            'medication_id' => $this->medicationId,
            'description' => $this->medicationId ? null : $this->searchTerm,
            'quantity' => $this->quantity,
            'dose' => $this->dose,
            'frequency' => $this->frequency,
            'duration' => $this->duration,
        ]);

        $this->reset(['medicationId', 'quantity', 'dose', 'frequency', 'duration', 'searchTerm']);
        $this->loadPrescriptions();
    }

    public function deletePrescription($id)
    {
        AppointmentPrescription::destroy($id);
        $this->loadPrescriptions();
    }

    public function save()
    {
        $this->missingAttachmentServiceNames = $this->getMissingAttachmentServiceNames();

        // open modal
        $this->dispatch('open-notes-modal');
    }

    public function updated($name, $value)
    {
        if (str_starts_with($name, 'form.services.') || str_starts_with($name, 'form.servicePrices.')) {
            // Al desmarcar un servicio, checkCouponAvailability() -> normalizeSelectedCouponIds()
            // descarta los cupones que ya no tienen servicios realizados.
            $this->syncSubtotalWithServices();
            $this->checkCouponAvailability();

            if (str_starts_with($name, 'form.services.') && $value) {
                $appointmentServiceId = (int) substr($name, strlen('form.services.'));
                $service = $this->services->firstWhere('id', $appointmentServiceId);

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
     * Persist a newly selected service as an AppointmentService marked as done.
     */
    public function registerNewService(array $serviceData): void
    {
        $appointmentService = AppointmentService::create([
            'appointment_id' => $this->appointment->id,
            'service_id' => $serviceData['id'] ?? null,
            'unregistered_service' => $serviceData['unregistered_service'] ?? null,
            'covered' => $serviceData['included'] ? 1 : 0,
            'status' => 'Completed',
        ]);

        $this->form->services[$appointmentService->id] = true;
        $this->form->attachments[$appointmentService->id] = null;
        $this->form->servicePrices[$appointmentService->id] = $this->defaultServicePrice($appointmentService);
    }

    /**
     * Reload the appointment services collection and recalculate totals.
     */
    public function refreshAppointmentServices(): void
    {
        $this->appointment->load('services.service');
        $this->services = $this->appointment->services;
        $this->syncSubtotalWithServices();
        $this->calculateTotals();
    }

    public function updatedSubtotal($value)
    {
        $this->calculateTotals();
    }

    /**
     * Sum the doctor's price for every service marked as completed and use it as the account subtotal.
     */
    private function syncSubtotalWithServices()
    {
        if ($this->hasReceptionistAssigned) {
            return;
        }

        $this->subtotal = number_format($this->calculateServicesSubtotal(), 2);
    }

    /**
     * Sum the price captured per service for every service marked as completed.
     */
    private function calculateServicesSubtotal()
    {
        return $this->services->reduce(function ($carry, $service) {
            if (empty($this->form->services[$service->id])) {
                return $carry;
            }

            $price = (float) str_replace(',', '', (string) ($this->form->servicePrices[$service->id] ?? 0));

            return $carry + $price;
        }, 0.0);
    }

    private function getDoctorPrices(array $serviceIds)
    {
        $doctorId = $this->appointment->doctor_id ?: $this->user->doctor->id;

        return DoctorService::where('doctor_id', $doctorId)
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
        $service = $this->services->firstWhere('service_id', $serviceId);

        if (! $service || empty($this->form->services[$service->id])) {
            return 0;
        }

        if ($service->subtotal !== null) {
            return (float) $service->subtotal;
        }

        $capturedPrice = $this->form->servicePrices[$service->id] ?? null;

        return $capturedPrice !== null && $capturedPrice !== ''
            ? (float) str_replace(',', '', (string) $capturedPrice)
            : $this->getServicePrice($serviceId);
    }

    /**
     * Sum the captured price of completed services covered by the policy (patient owes nothing for these).
     */
    private function calculateCoveredServicesAmount(): float
    {
        return $this->services->reduce(function ($carry, $service) {
            if (empty($this->form->services[$service->id]) || ! $service->covered) {
                return $carry;
            }

            $price = (float) str_replace(',', '', (string) ($this->form->servicePrices[$service->id] ?? 0));

            return $carry + $price;
        }, 0.0);
    }

    private function normalizeSelectedCouponIds(): void
    {
        $selectedIds = [];
        $usedServiceIds = [];

        foreach ($this->selectedCouponIds as $selectedId) {
            $benefit = $this->availableCoupons->firstWhere('id', (int) $selectedId);

            if (! $benefit) {
                continue;
            }

            if ($this->isGlobalCoupon($benefit)) {
                if ($selectedIds === []) {
                    $selectedIds[] = $benefit->id;
                }

                break;
            }

            $covered = $this->completedCouponServiceIds($benefit);

            // Ningún servicio cubierto está realizado, o ya lo usa otro cupón
            if ($covered === [] || array_intersect($covered, $usedServiceIds) !== []) {
                continue;
            }

            $selectedIds[] = $benefit->id;
            $usedServiceIds = array_merge($usedServiceIds, $covered);
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

                return $this->isGlobalCoupon($benefit)
                    || $this->completedCouponServiceIds($benefit) !== [];
            })
            ->values();
    }

    public function canSelectCoupon(int $benefitId): bool
    {
        $benefit = $this->availableCoupons->firstWhere('id', $benefitId);

        if (! $benefit || in_array($benefitId, $this->selectedCouponIds)) {
            return (bool) $benefit;
        }

        $isGlobal = $this->isGlobalCoupon($benefit);
        $covered = $this->completedCouponServiceIds($benefit);

        if (! $isGlobal && $covered === []) {
            return false;
        }

        foreach ($this->selectedCouponBenefits() as $selectedBenefit) {
            if ($isGlobal || $this->isGlobalCoupon($selectedBenefit)) {
                return false;
            }

            if (array_intersect($covered, $this->completedCouponServiceIds($selectedBenefit)) !== []) {
                return false;
            }
        }

        return true;
    }

    private function hasCompletedCouponService(int $serviceId): bool
    {
        return $this->services->contains(
            fn ($service) => (int) $service->service_id === $serviceId
                && ! empty($this->form->services[$service->id])
        );
    }

    /**
     * Actualiza los totales mostrados en pantalla a partir del desglose calculado.
     */
    private function calculateTotals()
    {
        if ($this->hasReceptionistAssigned) {
            return;
        }

        $this->checkCouponAvailability();
        $this->normalizeSelectedCouponIds();

        $breakdown = $this->buildBreakdown();

        if ($breakdown['subtotal'] !== null) {
            $this->subtotal = number_format($breakdown['subtotal'], 2);
        }

        $this->couponDiscountValue = (float) $breakdown['coupon_discount'];
        $this->user_payment = number_format($breakdown['user_payment'], 2);
        $this->commision = number_format($breakdown['commission'], 2);
        $this->total = number_format($breakdown['total'], 2);
    }

    /**
     * Calcula, sin modificar el estado del componente, los totales del appointment y el desglose por servicio
     * con una sola fórmula, de modo que la suma de los servicios coincide con el appointment.
     *
     * @return array{subtotal: float|null, coupon_discount: float, user_payment: float, commission: float, total: float, services: array<int, array<string, float>>}
     */
    private function buildBreakdown(): array
    {
        $doctor = $this->appointment->doctor ?: $this->user->doctor;
        $services = [];

        if (! $doctor) {
            $totalSubtotal = 0;

            foreach ($this->services as $service) {
                if (empty($this->form->services[$service->id])) {
                    continue;
                }

                $amount = $this->getCouponServiceAmount($service->service_id);
                $totalSubtotal += $amount;

                $services[$service->id] = [
                    'subtotal' => $amount,
                    'coupon_discount' => 0,
                    'user_payment' => $amount,
                    'commission' => 0,
                    'total' => $amount,
                ];
            }

            return [
                'subtotal' => null,
                'coupon_discount' => 0.0,
                'user_payment' => $totalSubtotal,
                'commission' => 0.0,
                'total' => $totalSubtotal,
                'services' => $services,
            ];
        }

        $doctorCommissionPercent = $doctor->commission / 100;
        $doctorDiscountPercent = $doctor->discount / 100;

        $selectedBenefits = $this->selectedCouponBenefits();
        $proportionalDiscounts = $this->calculateProportionalAmountCouponDiscounts($selectedBenefits);
        $serviceCouponMap = $this->mapCouponsToServices($selectedBenefits);

        $totalPatientPayment = 0;
        $totalInmaxPays = 0;
        $totalSubtotal = 0;
        $couponDiscountValue = 0;

        foreach ($this->services as $service) {
            if (empty($this->form->services[$service->id])) {
                continue;
            }

            $servicePrice = $this->getCouponServiceAmount($service->service_id);
            $totalSubtotal += $servicePrice;

            $coupon = $serviceCouponMap[$service->service_id] ?? null;
            $couponDiscount = 0;

            if ($coupon) {
                $benefit = $selectedBenefits->firstWhere('coupon.id', $coupon->id);
                $benefitId = $benefit ? $benefit->id : null;
                $patientCharge = $this->calculatePatientChargeWithCoupon($servicePrice, $coupon, $proportionalDiscounts, $service->service_id, $benefitId);
                $inmaxPays = $this->calculateInmaxPaysWithCoupon($servicePrice, $patientCharge, $coupon, $doctorDiscountPercent);
                $couponDiscount = $servicePrice - $patientCharge;
                $couponDiscountValue += $couponDiscount;
            } else {
                $patientCharge = $this->calculatePatientChargeNoCoupon($servicePrice, $doctorDiscountPercent);
                $inmaxPays = 0;
            }

            $serviceCommission = ($patientCharge * $doctorCommissionPercent) - $inmaxPays;

            $services[$service->id] = [
                'subtotal' => $servicePrice,
                'coupon_discount' => $couponDiscount,
                'user_payment' => $patientCharge,
                'commission' => $serviceCommission,
                'total' => $patientCharge - $serviceCommission,
            ];

            $totalPatientPayment += $patientCharge;
            $totalInmaxPays += $inmaxPays;
        }

        $inMaxCommission = ($totalPatientPayment * $doctorCommissionPercent) - $totalInmaxPays;

        return [
            'subtotal' => $totalSubtotal,
            'coupon_discount' => $couponDiscountValue,
            'user_payment' => $totalPatientPayment,
            'commission' => $inMaxCommission,
            'total' => $totalPatientPayment - $inMaxCommission,
            'services' => $services,
        ];
    }

    /**
     * Guarda el desglose financiero de cada servicio realizado.
     *
     * Usa la misma fórmula que el appointment (buildBreakdown). Cada servicio se redondea a 2 decimales y el residuo
     * de centavos se absorbe en el último servicio, para que la suma por servicio sea exactamente igual a
     * user_payment, commission y total del appointment.
     */
    private function persistServiceFinancials(): void
    {
        if ($this->hasReceptionistAssigned) {
            return;
        }

        $rows = collect($this->buildBreakdown()['services'])->map(fn ($row) => array_map(
            fn ($value) => round($value, 2),
            $row
        ));

        if ($rows->isEmpty()) {
            return;
        }

        $lastId = $rows->keys()->last();
        $targets = [
            'user_payment' => floatval(str_replace(',', '', (string) $this->user_payment)),
            'commission' => floatval(str_replace(',', '', (string) $this->commision)),
            'total' => floatval(str_replace(',', '', (string) $this->total)),
        ];

        foreach ($targets as $column => $target) {
            $diff = round($target - $rows->sum($column), 2);

            if ($diff !== 0.0) {
                $row = $rows[$lastId];
                $row[$column] = round($row[$column] + $diff, 2);
                $rows[$lastId] = $row;
            }
        }

        foreach ($this->services as $service) {
            if ($rows->has($service->id)) {
                $service->update($rows[$service->id]);
            }
        }
    }

    public function confirmNotes(?bool $willUploadResultsLater = null)
    {
        $hasMissingAttachments = ! empty($this->missingAttachmentServiceNames);

        if ($hasMissingAttachments && $willUploadResultsLater === null && $this->canFinalizeWithoutAttachments()) {
            $willUploadResultsLater = false;
        }

        if ($hasMissingAttachments && $willUploadResultsLater === null) {
            return;
        }

        try {
            $note = $this->form->store($this->appointment->id);

            if ($this->isEditing) {
                $updateData = [];

                if (! $this->hasReceptionistAssigned) {
                    $updateData['subtotal'] = str_replace(',', '', (string) $this->subtotal) ?: null;
                    $updateData['coupon_discount'] = 0;
                    $updateData['user_payment'] = str_replace(',', '', (string) $this->user_payment) ?: null;
                    $updateData['commission'] = str_replace(',', '', (string) $this->commision) ?: null;
                    $updateData['total'] = str_replace(',', '', (string) $this->total) ?: null;
                }

                if (! empty($this->prescriptions)) {
                    $updateData['status_prescription'] = 'Pending';
                }

                if (! empty($updateData)) {
                    $this->appointment->update($updateData);
                    $this->persistServiceFinancials();

                    $totalCouponDiscount = $this->appointment->services->sum('coupon_discount');
                    $this->appointment->update(['coupon_discount' => $totalCouponDiscount]);
                }

                $this->dispatch('close-notes-modal');
                session()->flash('appointment_note_id', $note);

                return $this->redirect(NotesConfirmationPage::class);
            }
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        }

        // reedem the corresponding cupon and mark the appointment as 'completed'
        $this->redeem($willUploadResultsLater);

        // close modal
        $this->dispatch('close-notes-modal');

        session()->flash('appointment_note_id', $note);

        return $this->redirect(NotesConfirmationPage::class);
    }

    public function redeem(?bool $willUploadResultsLater = null)
    {
        $policy = $this->appointment->user->policy;
        $policyId = $policy->type === 'Member' ? $policy->parent_policy_id : $policy->id;
        $doctorId = $this->user->doctor->id;
        $hasMissingAttachments = ! empty($this->missingAttachmentServiceNames);

        if ($hasMissingAttachments && $willUploadResultsLater === null && $this->canFinalizeWithoutAttachments()) {
            $willUploadResultsLater = false;
        }

        $status = match (true) {
            $hasMissingAttachments && $willUploadResultsLater === true => AppointmentStatus::RESULTS_PENDING,
            $hasMissingAttachments && $willUploadResultsLater === false => AppointmentStatus::COMPLETED,
            $hasMissingAttachments => AppointmentStatus::RESULTS_PENDING,
            default => AppointmentStatus::COMPLETED,
        };

        foreach ($this->services as $service) {
            // Skip if NOT marked as done
            if (empty($this->form->services[$service->id])) {
                continue;
            }

            $serviceId = $service->service_id;

            // Custom services have no catalog service_id and must not redeem universal benefits.
            if (! $serviceId) {
                continue;
            }

            // Search for a benefit that covers this service
            $benefit = PolicyService::where('policy_id', $policyId)
                ->where('service_id', $serviceId)
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

        // Redeem coupon if used
        if (! $this->hasReceptionistAssigned && (float) $this->appointment->coupon_discount <= 0) {
            foreach ($this->selectedCouponBenefits() as $selectedBenefit) {
                $selectedBenefit->increment('used');
                $this->storeRedeemedCoupon($selectedBenefit);
            }
        }

        $this->subtotal = str_replace(',', '', $this->subtotal);

        $updateData = [
            'doctor_id' => $doctorId,
            'status' => $status,
        ];

        if (! $this->hasReceptionistAssigned) {
            $updateData['subtotal'] = $this->subtotal ?: null;
            $updateData['coupon_discount'] = 0;
            $updateData['user_payment'] = str_replace(',', '', $this->user_payment) ?: null;
            $updateData['commission'] = str_replace(',', '', $this->commision) ?: null;
            $updateData['total'] = str_replace(',', '', $this->total) ?: null;
        }

        $this->appointment->update($updateData);
        $this->persistServiceFinancials();

        $totalCouponDiscount = $this->appointment->services->sum('coupon_discount');
        $this->appointment->update(['coupon_discount' => $totalCouponDiscount]);

        if (! empty($this->prescriptions)) { // has at least one element
            $this->appointment->update([
                'status_prescription' => 'Pending',
            ]);
        }

        if ($updateData['status'] === AppointmentStatus::COMPLETED) {
            app(AppointmentCompletedNotificationService::class)->send($this->appointment->fresh(['user', 'doctor.user', 'note']));
        }
    }

    /**
     * Marca el cupón en los servicios realizados que cubre (service_id directo o pivote).
     */
    private function storeRedeemedCoupon($benefit): void
    {
        if (! $benefit->coupon_id) {
            return;
        }

        $serviceIds = $this->couponServiceIds($benefit);

        $completedServices = $this->services->filter(fn ($service) => ! empty($this->form->services[$service->id]));

        if ($serviceIds !== []) {
            $completedServices = $completedServices->whereIn('service_id', $serviceIds);
        }

        foreach ($completedServices as $service) {
            $service->update(['coupon_id' => $benefit->coupon_id]);
        }
    }

    private function canFinalizeWithoutAttachments(): bool
    {
        $doctorType = $this->user?->doctor?->type;

        return $doctorType === DoctorType::Doctor;
    }

    private function getMissingAttachmentServiceNames(): array
    {
        $missingServiceNames = [];

        foreach ($this->services as $service) {
            if (empty($this->form->services[$service->id])) {
                continue;
            }

            if (empty($this->form->attachments[$service->id])) {
                $missingServiceNames[] = (string) $service->name;
            }
        }

        return $missingServiceNames;
    }

    private function mapCouponsToServices($selectedBenefits): array
    {
        $couponMap = [];

        foreach ($selectedBenefits as $benefit) {
            $coupon = $benefit->coupon;
            $couponServiceIds = $this->getServiceCouponServiceIds($coupon);

            // Map coupon to all its services
            foreach ($couponServiceIds as $serviceId) {
                if (! isset($couponMap[$serviceId])) {
                    $couponMap[$serviceId] = $coupon;
                }
            }
        }

        return $couponMap;
    }

    private function calculatePatientChargeWithCoupon(float $servicePrice, $coupon, ?array $proportionalDiscounts = null, ?int $serviceId = null, ?int $benefitId = null): float
    {
        $discount = 0;

        if ($coupon->type === 'Amount') {
            // Check if this is a multi-service Amount coupon with proportional discount
            $couponServiceIds = $this->getServiceCouponServiceIds($coupon);
            if (count($couponServiceIds) > 1 && $proportionalDiscounts !== null && $serviceId !== null && $benefitId !== null) {
                // Use pre-calculated proportional discount
                $discount = $proportionalDiscounts[$serviceId][$benefitId] ?? (float) $coupon->value;
            } else {
                // Single service or no proportional calculation
                $discount = (float) $coupon->value;
            }
        } elseif ($coupon->type === 'Percentage') {
            $discount = $servicePrice * ((float) $coupon->value / 100);
        } elseif ($coupon->type === 'Service') {
            $discount = $servicePrice;
        }

        return max(0, $servicePrice - $discount);
    }

    private function calculatePatientChargeNoCoupon(float $servicePrice, float $doctorDiscountPercent): float
    {
        return $servicePrice * (1 - $doctorDiscountPercent);
    }

    private function calculateInmaxPaysWithCoupon(float $servicePrice, float $patientCharge, $coupon, float $doctorDiscountPercent): float
    {
        $priceWithDiscount = $servicePrice * (1 - $doctorDiscountPercent);
        $inmaxPaysAmount = $priceWithDiscount - $patientCharge;
        if ($inmaxPaysAmount < 0) {
            $inmaxPaysAmount = 0;
        }

        $inmaxCoveragePercent = (float) ($coupon->inmax_coverage_percent ?? 0) / 100;

        return $inmaxPaysAmount * $inmaxCoveragePercent;
    }

    /** IDs de servicios que cubre un cupón (service_id directo o pivote). */
    private function getServiceCouponServiceIds($coupon): array
    {
        return $coupon->serviceIds();
    }

    private function calculateProportionalAmountCouponDiscounts($selectedBenefits): array
    {
        $discounts = []; // Format: [$serviceId][$benefitId] = discountAmount

        foreach ($selectedBenefits as $benefit) {
            $coupon = $benefit->coupon;

            if ($coupon->type !== 'Amount') {
                continue;
            }

            $couponServiceIds = $this->getServiceCouponServiceIds($coupon);
            if (count($couponServiceIds) <= 1) {
                continue;
            }

            $totalPrice = 0;
            $servicePrices = [];

            foreach ($this->services as $service) {
                if (empty($this->form->services[$service->id]) || ! in_array((int) $service->service_id, $couponServiceIds)) {
                    continue;
                }

                $servicePrice = $this->getCouponServiceAmount($service->service_id);
                $totalPrice += $servicePrice;
                $servicePrices[(int) $service->service_id] = $servicePrice;
            }

            if ($totalPrice > 0) {
                foreach ($servicePrices as $serviceId => $servicePrice) {
                    $proportion = $servicePrice / $totalPrice;
                    $discount = round((float) $coupon->value * $proportion, 2);
                    $discounts[$serviceId][$benefit->id] = $discount;
                }
            }
        }

        return $discounts;
    }
}