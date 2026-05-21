<?php

namespace App\Livewire\Mobile\Doctor;

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
use Livewire\Attributes\Layout;
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

        // Search for all available coupons for this doctor and the services in the appointment
        $this->availableCoupons = PolicyService::with('doctorCoupon.coupon')
            ->where('policy_id', $policyId)
            ->whereNotNull('doctor_coupon_id')
            ->whereColumn('used', '<', 'included')
            ->whereHas('doctorCoupon', function ($q) use ($doctorId, $serviceIds, $subtotal) {
                $q->where('doctor_id', $doctorId)
                  ->whereHas('coupon', function ($q2) use ($serviceIds, $subtotal) {
                      // Universal coupon (no service_id) or specific to one of the appointment services
                      $q2->where(function($q3) use ($serviceIds) {
                          $q3->whereNull('service_id')
                             ->orWhereIn('service_id', $serviceIds);
                      });

                      // New logic: check limits
                      $q2->where(function ($q3) use ($subtotal) {
                          $q3->where('limit_min', '<=', 0)
                             ->orWhere('limit_min', '<=', $subtotal);
                      })->where(function ($q3) use ($subtotal) {
                          $q3->where('limit_max', '<=', 0)
                             ->orWhere('limit_max', '>', $subtotal);
                      });
                  });
            })
            ->get();

        dd($this->availableCoupons);

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
            'quantity' => 'required|numeric|min:1',
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
                $coupon = $selectedBenefit->doctorCoupon->coupon;
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
        if ($this->selectedCouponId) {
            PolicyService::where('id', $this->selectedCouponId)->increment('used');
        }

        $this->subtotal = str_replace(',', '', $this->subtotal);

        $updateData = [
            'doctor_id' => $doctorId,
            'status' => \App\Enums\AppointmentStatus::COMPLETED,
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

        app(AppointmentCompletedNotificationService::class)->send($this->appointment->fresh(['user', 'doctor.user', 'note']));
    }
}
