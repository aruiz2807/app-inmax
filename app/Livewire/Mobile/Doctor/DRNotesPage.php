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
        $this->services = AppointmentService::where('appointment_id', $this->appointment->id)->get();
        $this->form->isDoctor = $this->user->doctor->type === DoctorType::Doctor;
        $this->hasReceptionistAssigned = $this->user->doctor
            ?->staff()
            ->where('profile', 'Receptionist')
            ->exists() ?? false;

        foreach ($this->services as $service) {
            $this->form->services[$service->id] = (string) $service->status === AppointmentStatus::COMPLETED->value;
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

    public function checkCouponAvailability()
    {
        $this->hasCouponAvailable = false;
        $this->availableCoupons = collect();

        $policy = $this->appointment->user->policy;
        if (! $policy) {
            $this->selectedCouponIds = [];

            return;
        }

        $policyId = $policy->type === 'Member' ? $policy->parent_policy_id : $policy->id;
        $doctorId = $this->user->doctor->id;
        $serviceIds = $this->services
            ->filter(fn ($service) => ! empty($this->form->services[$service->id]))
            ->pluck('service_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($serviceIds === []) {
            $this->selectedCouponIds = [];

            return;
        }

        $subtotal = floatval(str_replace(',', '', $this->subtotal));

        $couponCheck = function ($q) use ($serviceIds, $doctorId) {
            // Check if coupon is valid for this doctor (must be assigned to them)
            $q->whereHas('doctors', function ($dq) use ($doctorId) {
                $dq->where('doctor_id', $doctorId);
            });

            // Universal coupon (no service_id) or specific to one of the appointment services
            $q->where(function ($q2) use ($serviceIds) {
                $q2->whereNull('service_id')
                    ->orWhereIn('service_id', $serviceIds);
            });

            // Check limits
            /*$q->where(function ($q2) use ($subtotal) {
                $q2->where('limit_min', '<=', 0)
                   ->orWhere('limit_min', '<=', $subtotal);
            })->where(function ($q2) use ($subtotal) {
                $q2->where('limit_max', '<=', 0)
                   ->orWhere('limit_max', '>', $subtotal);
            });*/
        };

        // Search for all available coupons for this doctor and the services in the appointment
        $this->availableCoupons = PolicyService::with('coupon')
            ->where('policy_id', $policyId)
            ->whereColumn('used', '<', 'included')
            ->whereHas('coupon', $couponCheck)
            ->get()
            ->filter(function ($benefit) {
                $serviceId = $benefit->coupon->service_id;
                if (! $serviceId) {
                    return true;
                }

                $service = $this->services->firstWhere('service_id', $serviceId);

                return $service && ! empty($this->form->services[$service->id]);
            })
            ->values();

        if ($this->availableCoupons->isNotEmpty()) {
            $this->hasCouponAvailable = true;
        } else {
            $this->selectedCouponIds = [];
        }

        $this->normalizeSelectedCouponIds();
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
            if (str_starts_with($name, 'form.services.') && ! $value) {
                $appointmentServiceId = (int) substr($name, strlen('form.services.'));
                $service = $this->services->firstWhere('id', $appointmentServiceId);

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

    private function selectedCouponBenefits()
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
        return $this->services->contains(
            fn ($service) => (int) $service->service_id === $serviceId
                && ! empty($this->form->services[$service->id])
        );
    }

    private function calculateTotals()
    {
        if ($this->hasReceptionistAssigned) {
            return;
        }

        // Check if an included MG consultation is being performed
        $mgParam = Parameter::where('type', 'MG')->where('key', 'Consulta')->first();
        $mgServiceId = $mgParam ? (int) $mgParam->value : null;

        $isMGIncluded = false;
        if ($mgServiceId) {
            $isMGIncluded = $this->services->where('service_id', $mgServiceId)
                ->where('covered', 1)
                ->filter(fn ($s) => ! empty($this->form->services[$s->id]))
                ->isNotEmpty();
        }

        $costoParam = Parameter::where('type', 'MG')->where('key', 'Costo')->first();
        if ($costoParam && $isMGIncluded) {
            $this->subtotal = number_format($costoParam->value, 2);
        }

        $this->checkCouponAvailability();

        $subtotal = floatval(str_replace(',', '', $this->subtotal));
        // Services marked as "incluido" are covered by the policy, so the patient owes nothing for them.
        $coveredAmount = $this->calculateCoveredServicesAmount();
        $payableSubtotal = max(0, $subtotal - $coveredAmount);

        $doctor = $this->appointment->doctor ?: $this->user->doctor;
        $doc_discount = $doctor->discount / 100;
        $doc_commision = $doctor->commission / 100;

        $this->couponDiscountValue = 0;
        $this->normalizeSelectedCouponIds();
        $selectedBenefits = $this->selectedCouponBenefits();
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
        $memberDiscount = round($memberDiscountBase * $doc_discount, 2);

        // Determine effective subtotal (what the user pays)
        // If it's an included MG consultation, it's 100% covered (user pays 0)
        if ($isMGIncluded) {
            $effectiveSubtotal = 0;
        } elseif ($selectedBenefits->isNotEmpty()) {
            $effectiveSubtotal = max(0, $payableSubtotal - $this->couponDiscountValue - $memberDiscount);
        } else {
            $effectiveSubtotal = max(0, $payableSubtotal - $memberDiscount);
        }

        $this->user_payment = number_format($effectiveSubtotal, 2);

        // The commission Inmax charges the doctor
        $commission_amount = $effectiveSubtotal * $doc_commision;

        // Total for the doctor: Subtotal - Platform Commission - Discount/Coupon

        if ($isMGIncluded) {
            $this->total = number_format(200, 2);
            $this->commision = number_format(0, 2);
        } elseif ($selectedBenefits->isNotEmpty()) {
            $this->total = number_format($subtotal - $memberDiscount - $commission_amount, 2);
            $this->commision = number_format($effectiveSubtotal - ($subtotal - $memberDiscount - $commission_amount), 2);

            if (
                $selectedBenefits->count() > 1
                || $selectedBenefits->contains(fn ($benefit) => $benefit->coupon->type === 'Service')
                || $couponCoverageTotal > 0
            ) {
                $remainingCommission = $effectiveSubtotal * $doc_commision;

                $this->commision = number_format($remainingCommission - $couponCoverageTotal, 2);
                $this->total = number_format(
                    max(0, $effectiveSubtotal - $remainingCommission) + $couponCoverageTotal,
                    2
                );
            }
        } else {
            $this->total = number_format($subtotal - $memberDiscount - $commission_amount, 2);
            $this->commision = number_format($commission_amount, 2);
        }
    }

    /**
     * Distribute the appointment-level financial breakdown across every completed service,
     * proportionally to the price captured for each one.
     */
    private function persistServiceFinancials(): void
    {
        if ($this->hasReceptionistAssigned) {
            return;
        }

        $totalSubtotal = floatval(str_replace(',', '', (string) $this->subtotal));
        $totalCoupon = $this->couponDiscountValue;
        $totalUserPayment = floatval(str_replace(',', '', (string) $this->user_payment));
        $totalCommission = floatval(str_replace(',', '', (string) $this->commision));
        $totalAmount = floatval(str_replace(',', '', (string) $this->total));
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

            return round(
                $serviceAmount * round((float) $coupon->inmax_coverage_percent / 100, 2),
                2
            );
        });
        $coveredAmount = $this->services->reduce(function ($carry, $service) use ($serviceCouponIds) {
            if (empty($this->form->services[$service->id]) || ! $service->covered || in_array((int) $service->service_id, $serviceCouponIds, true)) {
                return $carry;
            }

            return $carry + (float) str_replace(',', '', (string) ($this->form->servicePrices[$service->id] ?? 0));
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

        foreach ($this->services as $service) {
            if (empty($this->form->services[$service->id])) {
                continue;
            }

            $price = (float) str_replace(',', '', (string) ($this->form->servicePrices[$service->id] ?? 0));

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

                return $coupon->type === 'Amount'
                    ? (float) $coupon->value
                    : round($serviceAmount * ((float) $coupon->value / 100), 2);
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
                    $updateData['coupon_discount'] = $this->couponDiscountValue ?: null;
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
            // dd($benefit);
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
        foreach ($this->selectedCouponBenefits() as $selectedBenefit) {
            $selectedBenefit->increment('used');
            $this->storeRedeemedCoupon($selectedBenefit->coupon_id, $selectedBenefit->coupon?->service_id);
        }

        $this->subtotal = str_replace(',', '', $this->subtotal);

        $updateData = [
            'doctor_id' => $doctorId,
            'status' => $status,
        ];

        if (! $this->hasReceptionistAssigned) {
            $updateData['subtotal'] = $this->subtotal ?: null;
            $updateData['coupon_discount'] = $this->couponDiscountValue ?: null;
            $updateData['user_payment'] = str_replace(',', '', $this->user_payment) ?: null;
            $updateData['commission'] = str_replace(',', '', $this->commision) ?: null;
            $updateData['total'] = str_replace(',', '', $this->total) ?: null;
        }

        $this->appointment->update($updateData);
        $this->persistServiceFinancials();

        if (! empty($this->prescriptions)) { // has at least one element
            $this->appointment->update([
                    'status_prescription' => 'Pending',
                ]);
        }

        if ($updateData['status'] === AppointmentStatus::COMPLETED) {
            app(AppointmentCompletedNotificationService::class)->send($this->appointment->fresh(['user', 'doctor.user', 'note']));
        }
    }

    private function storeRedeemedCoupon(?int $couponId, ?int $serviceId): void
    {
        if (! $couponId) {
            return;
        }

        $completedServices = $this->services->filter(fn ($service) => ! empty($this->form->services[$service->id]));

        if ($serviceId) {
            $completedServices = $completedServices->where('service_id', $serviceId);
        }

        foreach ($completedServices as $service) {
            $service->update(['coupon_id' => $couponId]);
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
}
