<?php

namespace App\Livewire\Forms;

use App\Models\AppointmentNote;
use App\Models\AppointmentService;
use Livewire\Form;
use Livewire\WithFileUploads;

class DoctorNotesForm extends Form
{
    use WithFileUploads;

    public $symptoms;
    public $findings;
    public $diagnosis;
    public $treatment;
    public $notes = '';
    public $attachments = [];
    public $services = [];
    public $isDoctor;

    /**
     * Additional rules
     */
    protected function rules()
    {
        $required = $this->isDoctor ? 'required' : 'nullable';

        return [
            'symptoms' => [$required, 'string'],
            'findings' => [$required, 'string'],
            'diagnosis' => [$required, 'string'],
            'treatment' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'services' => ['nullable', 'array',
                function ($attribute, $value, $fail) {
                    if (empty($value) || !collect($value)->contains(true)) {
                        $fail('Debe marcar al menos un servicio como realizado.');
                    }
                }],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ];
    }

    /**
    * Store the doctor notes in the DB.
    */
    public function store($appointmentId)
    {
        $this->validate();

        $appointmentServices = AppointmentService::whereIn('id', array_keys($this->services))->get()->keyBy('id');

        foreach ($this->services as $serviceId => $isDone)
        {
            if (!isset($appointmentServices[$serviceId])) {
                continue;
            }

            $data = [
                'status' => $isDone ? 'Completed' : 'Cancelled',
            ];

            if ($isDone && !empty($this->attachments[$serviceId]))
            {
                $file = $this->attachments[$serviceId];
                $path = $file->store('attachments');
                $originalName = $file->getClientOriginalName();

                $data['attachment_path'] = $path;
                $data['attachment_name'] = $originalName;
            }

            $appointmentServices[$serviceId]->update($data);
        }

        $note = AppointmentNote::create([
            'appointment_id' => $appointmentId,
            'symptoms' => $this->symptoms,
            'findings' => $this->findings,
            'diagnosis' => $this->diagnosis,
            'treatment' => $this->treatment,
            'notes' => $this->notes,
        ]);

        return $note->id;
    }
}
