<?php

namespace App\Livewire\Mobile\Doctor;

use App\Livewire\Forms\DoctorNotesForm;
use App\Livewire\Mobile\Doctor\NotesConfirmationPage;
use App\Enums\DoctorType;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\PolicyService;
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

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.notes-page');
    }

    public function mount($appointment)
    {
        $this->appointment = Appointment::findOrFail($appointment);
        $this->services = AppointmentService::where('appointment_id', $this->appointment->id)->get();
        $this->form->isDoctor = $this->appointment->doctor->type === DoctorType::Doctor;

        foreach ($this->services as $service)
        {
            $this->form->services[$service->id] = false;
            $this->form->attachments[$service->id] = null;
        }
    }

    public function save()
    {
        //open modal
        $this->dispatch('open-notes-modal');
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

            if($service->covered)
            {
                $benefit->increment('used');
            }
            else
            {
                $benefit->increment('extra');
            }

        }

        $this->appointment->update([
            'status' => 'Completed',
        ]);
    }
}




