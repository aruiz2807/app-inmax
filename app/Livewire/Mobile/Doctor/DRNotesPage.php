<?php

namespace App\Livewire\Mobile\Doctor;

use App\Livewire\Forms\DoctorNotesForm;
use App\Livewire\Mobile\Doctor\NotesConfirmationPage;
use App\Models\Appointment;
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

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.notes-page');
    }

    public function mount($appointment)
    {
        $this->appointment = Appointment::findOrFail($appointment);
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
        $service = $this->appointment->doctor->specialty->service;

        $benefit = PolicyService::where([
            ['policy_id', $policy->id],
            ['service_id', $service->id],
        ])->first();

        if($this->appointment->covered)
        {
            $benefit->update([
                'used' => $benefit->used + 1,
            ]);
        }
        else
        {
            $benefit->update([
                'extra' => $benefit->extra + 1,
            ]);
        }

        $this->appointment->update([
            'status' => 'Completed',
        ]);
    }
}




