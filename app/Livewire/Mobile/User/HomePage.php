<?php

namespace App\Livewire\Mobile\User;

use App\Models\Appointment;
use App\Models\Parameter;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class HomePage extends Component
{
    public $unratedAppointments;
    public $overflowUnratedAppointments;
    public $unratedAppointmentsCount;
    public $whatsappUrl = null;

    public function mount()
    {
        $whatsappNumber = Parameter::where('type', 'RS')->where('key', 'Whatsapp')->value('value');

        if (filled($whatsappNumber)) {
            $normalizedNumber = preg_replace('/\D/', '', $whatsappNumber);
            $this->whatsappUrl = filled($normalizedNumber)
                ? 'https://wa.me/' . $normalizedNumber
                : null;
        }

        $appointments = Appointment::where('user_id', Auth::user()->id)
            ->where('status', \App\Enums\AppointmentStatus::COMPLETED)
            ->whereNull('rating')
            ->with('doctor.user')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->setAppointmentsCollections($appointments);
    }

    public function dismissRatingAlert($appointmentId)
    {
        $appointments = $this->unratedAppointments
            ->merge($this->overflowUnratedAppointments)
            ->reject(function ($appointment) use ($appointmentId) {
                return $appointment->id == $appointmentId;
            })
            ->values();

        $this->setAppointmentsCollections($appointments);
    }

    protected function setAppointmentsCollections($appointments): void
    {
        $this->unratedAppointments = $appointments->take(2)->values();
        $this->overflowUnratedAppointments = $appointments->skip(2)->values();
        $this->unratedAppointmentsCount = $this->overflowUnratedAppointments->count();
    }

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.home');
    }
}
