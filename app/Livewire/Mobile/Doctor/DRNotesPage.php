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
        $this->appointment = Appointment::findOrFail($appointment);
        $this->services = AppointmentService::where('appointment_id', $this->appointment->id)->get();
        $this->form->isDoctor = $this->user->doctor->type === DoctorType::Doctor;

        foreach ($this->services as $service)
        {
            $this->form->services[$service->id] = false;
            $this->form->attachments[$service->id] = null;
        }

        $this->loadMedications();
        $this->loadPrescriptions();
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
        $subtotal = floatval(str_replace(',', '', $value));

        if($this->appointment->doctor)
        {
            $doc_discount = $this->appointment->doctor->discount/100;
            $doc_commision = $this->appointment->doctor->commission/100;
        }
        else
        {
            $doc_discount = $this->user->doctor->discount/100;
            $doc_commision = $this->user->doctor->commission/100;
        }

        $discount = round($subtotal * $doc_discount, 2);
        $this->user_payment = number_format($subtotal - $discount, 2);
        $this->commision = number_format($subtotal * $doc_commision, 2);
        $this->total = number_format($subtotal - $discount - floatval(str_replace(',', '', $this->commision)), 2);

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

        foreach($this->services as $service)
        {
            // Skip if NOT marked as done
            if (empty($this->form->services[$service->id])) {
                continue;
            }

            $benefit = PolicyService::where([
                ['policy_id', $policyId],
                ['service_id', $service->service_id],
            ])->first();

            if($benefit)
            {
                if($service->covered)
                {
                    $benefit->increment('used');
                }
                else
                {
                    $benefit->increment('extra');
                }
            }
        }

        $this->subtotal = str_replace(',', '', $this->subtotal);

        $this->appointment->update([
            'subtotal' => $this->subtotal ?: '0.00',
            'doctor_id' => $this->user->doctor->id,
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
