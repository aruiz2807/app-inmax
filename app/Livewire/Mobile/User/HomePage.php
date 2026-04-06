<?php

namespace App\Livewire\Mobile\User;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class HomePage extends Component
{
    public $unratedAppointments;

    public function mount()
    {
        $this->unratedAppointments = Appointment::where('user_id', Auth::user()->id)
            ->where('status', 'Completed')
            ->whereNull('rating')
            ->with('doctor.user')
            ->get();
    }

    public function dismissRatingAlert($appointmentId)
    {
        $this->unratedAppointments = $this->unratedAppointments->reject(function ($appointment) use ($appointmentId) {
            return $appointment->id == $appointmentId;
        })->values();
    }

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.home');
    }
}
