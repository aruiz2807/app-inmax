<?php

namespace App\Livewire\Mobile\User;

use App\Enums\DoctorType;
use App\Models\Appointment;
use App\Models\AppointmentService;
use Livewire\Attributes\Layout;
use Livewire\Component;

class NotesPage extends Component
{
    public $appointment;
    public $services;
    public $isDoctor;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.notes-page');
    }

    public function mount($appointment)
    {
        $this->appointment = Appointment::findOrFail($appointment);
        $this->services = AppointmentService::where([
            ['appointment_id', $this->appointment->id],
            ['status', \App\Enums\AppointmentStatus::COMPLETED],
        ])->get();
        $this->isDoctor = $this->appointment->doctor->type === DoctorType::Doctor;
    }
}
