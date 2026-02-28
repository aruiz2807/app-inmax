<?php

namespace App\Livewire\Appointments;

use App\Models\Appointment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class AppointmentsPage extends Component
{
    public $appointmentId;
    public $appointment;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.appointments.appointments-page');
    }

    #[On('editAppointment')]
    public function edit($appointmentId)
    {
        $this->appointment = Appointment::find($appointmentId);
        $this->appointmentId = $appointmentId;

        //open modal
        $this->dispatch('open-appointment-modal');
    }

    #[On('cancelAppointment')]
    public function cancel($appointmentId)
    {
        $this->appointment = Appointment::find($appointmentId);

        //open modal
        $this->dispatch('open-cancel-appointment-modal');
    }

    public function confirmCancel()
    {
        $this->appointment->update([
            'status' => 'Cancelled',
        ]);

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Cita cancelada exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-cancel-appointment-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-appointmentsTable');
    }

    public function resetForm()
    {
        $this->appointmentId = null;
    }
}
