<?php

namespace App\Livewire\Mobile\Doctor;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

class DRRequestsPage extends Component
{
    public $requests = null;
    public $appointmentId = null;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.requests-page');
    }

    public function mount()
    {
        $user = Auth::user();

        $this->requests = Appointment::where([
                ['status', \App\Enums\AppointmentStatus::REQUESTED],
                ['doctor_id', $user->doctor->id],
            ])
            ->orderBy('date')
            ->orderBy('time')
            ->get();
    }

    public function accept($id)
    {
        $this->appointmentId = $id;

        //open modal
        $this->dispatch('open-accept-modal');
    }

    public function reject($id)
    {
        $this->appointmentId = $id;

        //open modal
        $this->dispatch('open-reject-modal');
    }

    public function acceptAppointment()
    {
        $appointment = Appointment::findOrFail($this->appointmentId);

        $appointment->update([
            'status' => \App\Enums\AppointmentStatus::BOOKED,
        ]);

        //close modal
        $this->dispatch('close-accept-modal');

        session()->flash('appointment_accept_id', $appointment->id);

        return $this->redirect(AcceptConfirmationPage::class);
    }

    public function rejectAppointment()
    {
        $appointment = Appointment::findOrFail($this->appointmentId);

        $appointment->update([
            'status' => \App\Enums\AppointmentStatus::REJECTED,
        ]);

        //close modal
        $this->dispatch('close-reject-modal');

        session()->flash('appointment_reject_id', $appointment->id);

        return $this->redirect(RejectConfirmationPage::class);
    }
}
