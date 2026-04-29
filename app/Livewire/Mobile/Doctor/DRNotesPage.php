<?php

namespace App\Livewire\Mobile\Doctor;

use App\Livewire\Forms\DoctorNotesForm;
use App\Livewire\Mobile\Doctor\NotesConfirmationPage;
use App\Enums\DoctorType;
use App\Models\Appointment;
use App\Models\Medication;
use App\Models\AppointmentPrescription;
use App\Models\AppointmentService;
use App\Models\PolicyService;
use App\Services\Appointments\AppointmentCompletedNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
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

    // Coupon logic
    public bool $useCoupon = false;
    public bool $hasCouponAvailable = false;
    public $availableCouponBenefit = null;
    public float $couponDiscountValue = 0;

    // Medication selection
    public $medications = [];
    public $prescriptions = [];
    public $medicationId = null;
    public $quantity = 1;
    public $dose = '';
    public $frequency = '';
    public $duration = '';

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.notes-page');
    }

    public function mount($appointment)
    {
        $this->user = Auth::user();
        $this->appointment = Appointment::with(['user.policy', 'doctor'])->findOrFail($appointment);
        $this->services = AppointmentService::where('appointment_id', $this->appointment->id)->get();
        $this->form->isDoctor = $this->user->doctor->type === DoctorType::Doctor;

        foreach ($this->services as $service)
        {
            $this->form->services[$service->id] = false;
            $this->form->attachments[$service->id] = null;
        }

        $this->loadMedications();
        $this->loadPrescriptions();
        $this->checkCouponAvailability();
    }

    public function loadMedications()
    {
        $this->medications = Medication::where('status', 'Active')->get();
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
        $this->availableCouponBenefit = null;

        $policy = $this->appointment->user->policy;
        if (!$policy) return;

        $policyId = $policy->type === 'Member' ? $policy->parent_policy_id : $policy->id;
        $doctorId = $this->user->doctor->id;
        $serviceIds = $this->services->pluck('service_id')->toArray();

        // Search for an available coupon for this doctor and the services in the appointment
        $this->availableCouponBenefit = PolicyService::with('doctorCoupon.coupon')
            ->where('policy_id', $policyId)
            ->whereNotNull('doctor_coupon_id')
            ->whereColumn('used', '<', 'included')
            ->whereHas('doctorCoupon', function ($q) use ($doctorId, $serviceIds) {
                $q->where('doctor_id', $doctorId)
                  ->whereHas('coupon', function ($q2) use ($serviceIds) {
                      // Universal coupon (no service_id) or specific to one of the appointment services
                      $q2->whereNull('service_id')
                         ->orWhereIn('service_id', $serviceIds);
                  });
            })
            ->first();

        if ($this->availableCouponBenefit) {
            $this->hasCouponAvailable = true;
        }
    }

    public function addMedication()
    {
        $this->validate([
            'medicationId' => 'required|exists:medications,id',
            'quantity' => 'required|numeric|min:1',
            'dose' => 'required|string|max:50',
            'frequency' => 'required|string|max:50',
            'duration' => 'required|string|max:50',
        ]);

        AppointmentPrescription::create([
            'appointment_id' => $this->appointment->id,
            'medication_id' => $this->medicationId,
            'quantity' => $this->quantity,
            'dose' => $this->dose,
            'frequency' => $this->frequency,
            'duration' => $this->duration,
        ]);

        $this->reset(['medicationId', 'quantity', 'dose', 'frequency', 'duration']);
        $this->loadPrescriptions();
    }

    public function deletePrescription($id)
    {
        AppointmentPrescription::destroy($id);
        $this->loadPrescriptions();
    }

    public function save()
    {
        //open modal
        $this->dispatch('open-notes-modal');
    }

    public function updatedSubtotal($value)
    {
        $this->calculateTotals();
    }

    public function updatedUseCoupon($value)
    {
        $this->calculateTotals();
    }

    private function calculateTotals()
    {
        $subtotal = floatval(str_replace(',', '', $this->subtotal));
        
        $doctor = $this->appointment->doctor ?: $this->user->doctor;
        $doc_discount = $doctor->discount / 100;
        $doc_commision = $doctor->commission / 100;

        // By default, the patient discount is calculated based on the doctor's profile discount
        $memberDiscount = round($subtotal * $doc_discount, 2);
        
        $this->couponDiscountValue = 0;
        if ($this->useCoupon && $this->availableCouponBenefit) {
            $coupon = $this->availableCouponBenefit->doctorCoupon->coupon;
            if ($coupon->type === 'Amount') {
                $this->couponDiscountValue = (float) $coupon->value;
            } elseif ($coupon->type === 'Percentage') {
                $this->couponDiscountValue = round($subtotal * ($coupon->value / 100), 2);
            }
        }

        // If a coupon is used, it completely overrides the standard member discount for the user payment calculation
        if ($this->useCoupon) {
            $effectiveSubtotal = max(0, $subtotal - $this->couponDiscountValue);
        } else {
            $effectiveSubtotal = max(0, $subtotal - $memberDiscount);
        }
        
        $this->user_payment = number_format($effectiveSubtotal, 2);
        
        // The commission Inmax charges the doctor is usually based on the full subtotal
        $this->commision = number_format($subtotal * $doc_commision, 2);
        
        // Total for the doctor: Subtotal - Platform Commission - Coupon (or member discount) 
        if ($this->useCoupon) {
            //$this->total = number_format($subtotal - $this->couponDiscountValue - floatval(str_replace(',', '', $this->commision)), 2);
            $this->total = number_format($subtotal * (100 - $doc_discount + $doc_commision));
            $this->commision = $this->total - $this->user_payment;
        } else {
            $this->total = number_format($subtotal - $memberDiscount - floatval(str_replace(',', '', $this->commision)), 2);
        }
    }

    public function confirmNotes()
    {
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
        $this->redeem();

        //close modal
        $this->dispatch('close-notes-modal');

        session()->flash('appointment_note_id', $note);

        return $this->redirect(NotesConfirmationPage::class);
    }

    public function redeem()
    {
        $policy = $this->appointment->user->policy;
        $policyId = $policy->type === 'Member' ? $policy->parent_policy_id : $policy->id;
        $doctorId = $this->user->doctor->id;

        foreach($this->services as $service)
        {
            // Skip if NOT marked as done
            if (empty($this->form->services[$service->id])) {
                continue;
            }

            $serviceId = $service->service_id;

            // Search for a benefit that covers this service (excluding coupons)
            $benefit = PolicyService::where('policy_id', $policyId)
                ->where(function ($query) use ($serviceId, $doctorId) {
                    $query->where('service_id', $serviceId)
                          ->orWhereHas('doctorService', function ($q) use ($serviceId, $doctorId) {
                              $q->where('doctor_id', $doctorId)
                                ->where('service_id', $serviceId);
                          });
                })
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
        if ($this->useCoupon && $this->availableCouponBenefit) {
            $this->availableCouponBenefit->increment('used');
        }

        $this->subtotal = str_replace(',', '', $this->subtotal);

        $this->appointment->update([
            'subtotal' => $this->subtotal ?: '0.00',
            'coupon_discount' => $this->couponDiscountValue ?: '0.00',
            'user_payment' => str_replace(',', '', $this->user_payment) ?: '0.00',
            'commission' => str_replace(',', '', $this->commision) ?: '0.00',
            'total' => str_replace(',', '', $this->total) ?: '0.00',
            'doctor_id' => $doctorId,
            'status' => \App\Enums\AppointmentStatus::COMPLETED,
        ]);

        if (!empty($this->prescriptions)) // has at least one element
        { 
            $this->appointment->update([               
                'status_prescription' => 'Pending',
            ]);
        }

        app(AppointmentCompletedNotificationService::class)->send($this->appointment->fresh(['user', 'doctor.user', 'note']));
    }
}
