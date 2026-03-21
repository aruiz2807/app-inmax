<?php

namespace App\Livewire\Mobile\Doctor;

use App\Enums\DoctorType;
use App\Models\Appointment;
use App\Models\AppointmentService;
use Livewire\Attributes\Layout;
use Livewire\Component;


class DRHistoryNotePage extends Component
{
    public $appointment;
    public $services;
    public $isDoctor;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.history-note-page');
    }

    public function mount($appointment)
    {
        $this->appointment = Appointment::findOrFail($appointment);
        $this->services = AppointmentService::where([
            ['appointment_id', $this->appointment->id],
            ['status', 'Completed'],
        ])->get();
        $this->isDoctor = $this->appointment->doctor->type === DoctorType::Doctor;
    }
}
