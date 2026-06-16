<?php

namespace App\Livewire\Mobile\User;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class HomePage extends Component
{
    public $unratedAppointments;
    public $unratedAppointmentsCount;

    public function mount()
    {
        $this->unratedAppointments = Appointment::where('user_id', Auth::user()->id)
            ->where('status', \App\Enums\AppointmentStatus::COMPLETED)
            ->whereNull('rating')
            ->with('doctor.user')
            ->orderBy('created_at', 'desc')
            ->limit(2)
            ->get();
        $this->unratedAppointmentsCount = Appointment::where('user_id', Auth::user()->id)
            ->where('status', \App\Enums\AppointmentStatus::COMPLETED)
            ->whereNull('rating')
            ->with('doctor.user')
            ->count() - 2;
    }

    public function dismissRatingAlert($appointmentId)
    {
        $this->unratedAppointments = $this->unratedAppointments->reject(function ($appointment) use ($appointmentId) {
            return $appointment->id == $appointmentId;
        })->values();
        $this->unratedAppointmentsCount = $this->unratedAppointmentsCount - 1;
    }

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.home');
    }
}
