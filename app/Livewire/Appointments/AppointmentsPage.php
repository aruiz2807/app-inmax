<?php

namespace App\Livewire\Appointments;

use App\Enums\AppointmentStatus;
use App\Livewire\Concerns\WithPatientHistoryModal;
use App\Models\Appointment;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class AppointmentsPage extends Component
{
    use WithFileUploads;
    use WithPatientHistoryModal;

    #[Url(as: 'tab')]
    public string $tab = 'booked';

    public $appointmentId;
    public $appointment;

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['all', 'booked', 'completed', 'cancelled'], true)) {
            return;
        }

        $this->tab = $tab;
    }

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
            'status' => \App\Enums\AppointmentStatus::CANCELLED,
        ]);

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'¡Cita cancelada exitosamente!',
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
        $this->resetPatientHistory();
    }

    #[On('orderAppointment')]
    public function orderAppointment($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        $pdf = Pdf::loadView('pdf.order', [
            'appointment' => $appointment,
            'contactEmail' => \App\Models\Parameter::where('type', 'RS')->where('key', 'Email')->value('value') ?? 'contacto@inmax.mx'
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "order-{$appointment->id}.pdf"
        );
    }
}
