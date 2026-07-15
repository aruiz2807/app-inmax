<?php

namespace App\Livewire\Mobile\Doctor;

use App\Enums\AppointmentStatus;
use App\Livewire\Forms\DoctorNotesForm;
use App\Livewire\Mobile\Doctor\NotesConfirmationPage;
use App\Enums\DoctorType;
use App\Models\Appointment;
use App\Models\Medication;
use App\Models\AppointmentPrescription;
use App\Models\AppointmentService;
use App\Models\Parameter;
use App\Models\PolicyService;
use App\Services\Appointments\AppointmentCompletedNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class DRNotesPage extends Component
{
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
    public $selectedCouponId = null;
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
        !$desktopVersionEnabled ? $this->isMobileDevice = true : '';

        $this->user = Auth::user();
        $this->appointment = Appointment::with(['user.policy', 'doctor'])->findOrFail($appointment);
        $this->services = AppointmentService::where('appointment_id', $this->appointment->id)->get();
        $this->form->isDoctor = $this->user->doctor->type === DoctorType::Doctor;
        $this->hasReceptionistAssigned = $this->user->doctor
            ?->staff()
            ->where('profile', 'Receptionist')
            ->exists() ?? false;

        foreach ($this->services as $service)
        {
            $this->form->services[$service->id] = false;
            $this->form->attachments[$service->id] = null;
        }

        $this->loadPrescriptions();
        $this->checkCouponAvailability();
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
                $query->where('name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('trade_name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('active_substance', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('packaging', 'like', '%' . $this->searchTerm . '%');
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
        if (!$policy) return;

        $policyId = $policy->type === 'Member' ? $policy->parent_policy_id : $policy->id;
        $doctorId = $this->user->doctor->id;
        $serviceIds = $this->services->pluck('service_id')->toArray();
        
        $subtotal = floatval(str_replace(',', '', $this->subtotal));

        $couponCheck = function ($q) use ($serviceIds, $subtotal, $doctorId) {
            // Check if coupon is valid for this doctor (must be assigned to them)
            $q->whereHas('doctors', function ($dq) use ($doctorId) {
                $dq->where('doctor_id', $doctorId);
            });

            // Universal coupon (no service_id) or specific to one of the appointment services
            $q->where(function($q2) use ($serviceIds) {
                $q2->whereNull('service_id')
                   ->orWhereIn('service_id', $serviceIds);
            });

            // Check limits
            $q->where(function ($q2) use ($subtotal) {
                $q2->where('limit_min', '<=', 0)
                   ->orWhere('limit_min', '<=', $subtotal);
            })->where(function ($q2) use ($subtotal) {
                $q2->where('limit_max', '<=', 0)
                   ->orWhere('limit_max', '>', $subtotal);
            });
        };

        // Search for all available coupons for this doctor and the services in the appointment
        $this->availableCoupons = PolicyService::with(['coupon'])
            ->where('policy_id', $policyId)
            ->whereColumn('used', '<', 'included')
            ->whereNotNull('coupon_id')
            ->whereHas('coupon', $couponCheck)
            ->get();

        if ($this->availableCoupons->isNotEmpty()) {
            $this->hasCouponAvailable = true;
            // Check if selected coupon is still available, if not, reset it
            if ($this->selectedCouponId && !$this->availableCoupons->contains('id', $this->selectedCouponId)) {
                $this->selectedCouponId = null;
            }
        } else {
            $this->selectedCouponId = null;
        }
    }

    public function addMedication()
    {
        $this->validate([
            'medicationId' => 'nullable|exists:medications,id',
            'searchTerm' => 'required|string|max:250',
            'quantity' => 'required|string|max:20',
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

        //open modal
        $this->dispatch('open-notes-modal');
    }

    public function updated($name, $value)
    {
        if (str_starts_with($name, 'form.services.')) {
            $this->calculateTotals();
        }
    }

    public function updatedSubtotal($value)
    {
        $this->calculateTotals();
    }

    public function updatedSelectedCouponId($value)
    {
        $this->calculateTotals();
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
                ->filter(fn($s) => !empty($this->form->services[$s->id]))
                ->isNotEmpty();
        }

        // Auto-set subtotal if it's an included MG consultation and current subtotal is empty
        if ($isMGIncluded && (empty($this->subtotal) || floatval(str_replace(',', '', $this->subtotal)) == 0)) {
            $costoParam = Parameter::where('type', 'MG')->where('key', 'Costo')->first();
            if ($costoParam) {
                $this->subtotal = number_format($costoParam->value, 2);
            }
        }

        $this->checkCouponAvailability();

        $subtotal = floatval(str_replace(',', '', $this->subtotal));
        
        $doctor = $this->appointment->doctor ?: $this->user->doctor;
        $doc_discount = $doctor->discount / 100;
        $doc_commision = $doctor->commission / 100;

        // By default, the patient discount is calculated based on the doctor's profile discount
        $memberDiscount = round($subtotal * $doc_discount, 2);
        
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

        // Determine effective subtotal (what the user pays)
        // If it's an included MG consultation, it's 100% covered (user pays 0)
        if ($isMGIncluded) {
            $effectiveSubtotal = 0;
        } elseif ($selectedBenefit) {
            $effectiveSubtotal = max(0, $subtotal - $this->couponDiscountValue);
        } else {
            $effectiveSubtotal = max(0, $subtotal - $memberDiscount);
        }

        $this->user_payment = number_format($effectiveSubtotal, 2);
        
        // The commission Inmax charges the doctor
        $commission_amount = $subtotal * $doc_commision;
        
        // Total for the doctor: Subtotal - Platform Commission - Discount/Coupon
        if ($isMGIncluded) {
            $this->total = number_format($subtotal - $memberDiscount - $commission_amount, 2);
            $this->commision = number_format(0, 2);
        } elseif ($selectedBenefit) {
            $this->total = number_format($subtotal - $memberDiscount - $commission_amount, 2);
            $this->commision = number_format($effectiveSubtotal - ($subtotal - $memberDiscount - $commission_amount), 2);
        } else {
            $this->total = number_format($subtotal - $memberDiscount - $commission_amount, 2);
            $this->commision = number_format($commission_amount, 2);
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

        try
        {
            $note = $this->form->store($this->appointment->id);
        }
        catch (ValidationException $e)
        {
            $this->setErrorBag($e->validator->errors());
            return;
        }

        //reedem the corresponding cupon and mark the appointment as 'completed'
        $this->redeem($willUploadResultsLater);

        //close modal
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

        foreach($this->services as $service)
        {
            // Skip if NOT marked as done
            if (empty($this->form->services[$service->id])) {
                continue;
            }

            $serviceId = $service->service_id;

            // Search for a benefit that covers this service
            $benefit = PolicyService::where('policy_id', $policyId)
                ->where('service_id', $serviceId)
                ->orderByRaw('used < included DESC') // Prioritize ones with remaining space
                ->first();

            if($benefit)
            {
                if($benefit->used < $benefit->included)
                {
                    $benefit->increment('used');
                }
                else
                {
                    $benefit->increment('extra');
                }
            }
        }

        // Redeem coupon if used
        if ($this->selectedCouponId) {
            PolicyService::where('id', $this->selectedCouponId)->increment('used');
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

        if (!empty($this->prescriptions)) // has at least one element
        { 
            $this->appointment->update([               
                'status_prescription' => 'Pending',
            ]);
        }

        if ($updateData['status'] === AppointmentStatus::COMPLETED) {
            app(AppointmentCompletedNotificationService::class)->send($this->appointment->fresh(['user', 'doctor.user', 'note']));
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
