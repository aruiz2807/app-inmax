<?php

namespace App\Livewire\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class AppointmentsPage extends Component
{
    use WithFileUploads;

    public $appointmentId;
    public $appointment;
    public $historyPatient;
    public $historyAppointments;
    public array $historyServiceAttachments = [];
    public array $historyResultsComments = [];

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

    #[On('historyAppointment')]
    public function history($appointmentId)
    {
        $selectedAppointment = Appointment::query()
            ->with([
                'user.policy',
                'doctor.user',
                'doctor.specialty',
                'office',
            ])
            ->find($appointmentId);

        if (! $selectedAppointment) {
            return;
        }

        $this->historyPatient = $selectedAppointment->user;

        $this->historyAppointments = Appointment::query()
            ->with([
                'doctor.user',
                'doctor.specialty',
                'office',
                'note',
                'prescriptions.medication',
                'services.service',
            ])
            ->where('user_id', $selectedAppointment->user_id)
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get();

        $this->initializeHistoryUploadState();

        $this->dispatch('open-history-appointment-modal');
    }

    public function saveHistoryServiceAttachment(int $appointmentId, int $serviceId): void
    {
        $this->validate([
            "historyServiceAttachments.$appointmentId.$serviceId" => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            "historyServiceAttachments.$appointmentId.$serviceId.required" => 'Seleccione un archivo para adjuntar.',
            "historyServiceAttachments.$appointmentId.$serviceId.mimes" => 'El archivo debe ser PDF, JPG o PNG.',
            "historyServiceAttachments.$appointmentId.$serviceId.max" => 'El archivo no debe superar 2MB.',
        ]);

        $appointment = Appointment::query()
            ->with('services')
            ->where('id', $appointmentId)
            ->where('user_id', $this->historyPatient?->id)
            ->first();

        if (! $appointment) {
            return;
        }

        $service = $appointment->services
            ->where('status', AppointmentStatus::COMPLETED->value)
            ->firstWhere('id', $serviceId);

        if (! $service) {
            return;
        }

        $file = data_get($this->historyServiceAttachments, "$appointmentId.$serviceId");
        $path = $file->store('attachments');

        $service->update([
            'attachment_path' => $path,
            'attachment_name' => $file->getClientOriginalName(),
        ]);

        $this->refreshHistoryAppointments();

        $this->dispatch('notify',
            type: 'success',
            content: 'Archivo cargado correctamente.',
            duration: 3000
        );
    }

    public function saveHistoryResultsComment(int $appointmentId): void
    {
        $this->validate([
            "historyResultsComments.$appointmentId" => ['nullable', 'string', 'max:2000'],
        ], [
            "historyResultsComments.$appointmentId.max" => 'El enlace no debe superar 2000 caracteres.',
        ]);

        $appointment = Appointment::query()
            ->with('note')
            ->where('id', $appointmentId)
            ->where('user_id', $this->historyPatient?->id)
            ->first();

        if (! $appointment) {
            return;
        }

        $resultsComment = trim((string) data_get($this->historyResultsComments, $appointmentId, ''));

        $appointment->note()->updateOrCreate(
            ['appointment_id' => $appointment->id],
            ['results_comment' => $resultsComment !== '' ? $resultsComment : null]
        );

        $this->refreshHistoryAppointments();

        $this->dispatch('notify',
            type: 'success',
            content: 'Enlace de resultados guardado correctamente.',
            duration: 3000
        );
    }

    private function refreshHistoryAppointments(): void
    {
        if (! $this->historyPatient?->id) {
            return;
        }

        $this->historyAppointments = Appointment::query()
            ->with([
                'doctor.user',
                'doctor.specialty',
                'office',
                'note',
                'prescriptions.medication',
                'services.service',
            ])
            ->where('user_id', $this->historyPatient->id)
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get();

        $this->initializeHistoryUploadState();
    }

    private function initializeHistoryUploadState(): void
    {
        $this->historyServiceAttachments = [];
        $this->historyResultsComments = [];

        foreach ($this->historyAppointments ?? [] as $historyItem) {
            $this->historyResultsComments[$historyItem->id] = $historyItem->note?->results_comment;

            $completedServices = $historyItem->services
                ->where('status', AppointmentStatus::COMPLETED->value);

            foreach ($completedServices as $service) {
                $this->historyServiceAttachments[$historyItem->id][$service->id] = null;
            }
        }
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
        $this->historyPatient = null;
        $this->historyAppointments = null;
        $this->historyServiceAttachments = [];
        $this->historyResultsComments = [];
    }
}
