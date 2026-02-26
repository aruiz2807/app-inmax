<?php

namespace App\Livewire\Forms;

use App\Models\AppointmentNote;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Livewire\WithFileUploads;

class DoctorNotesForm extends Form
{
    use WithFileUploads;

    #[Validate('required|string')]
    public $symptoms;

    #[Validate('required|string')]
    public $findings;

    #[Validate('required|string')]
    public $diagnosis;

    #[Validate('required|string')]
    public $treatment;

    #[Validate('string')]
    public $notes = '';

    #[Validate('nullable|file|mimes:pdf,jpg,jpeg,png|max:2048')]
    public $attachment;

    /**
    * Store the doctor in the DB.
    */
    public function store($appointmentId)
    {
        $this->validate();

        $path = null;
        $originalName = null;

        if ($this->attachment) {
            $path = $this->attachment->store('attachments');
            $originalName = $this->attachment->getClientOriginalName();
        }

        $note = AppointmentNote::create([
            'appointment_id' => $appointmentId,
            'symptoms' => $this->symptoms,
            'findings' => $this->findings,
            'diagnosis' => $this->diagnosis,
            'treatment' => $this->treatment,
            'notes' => $this->notes,
            'attachment_path' => $path,
            'attachment_name' => $originalName,
        ]);

        return $note->id;
    }
}
