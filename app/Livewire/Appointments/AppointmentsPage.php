<?php

namespace App\Livewire\Appointments;

use App\Enums\AppointmentStatus;
use App\Enums\ExternalServicesType;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\PolicyExternalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class AppointmentsPage extends Component
{
    use WithFileUploads;

    #[Url(as: 'tab')]
    public string $tab = 'booked';

    public $appointmentId;
    public $appointment;
    public $historyPatient;
    public $historyAppointments;
    public array $historyServiceAttachments = [];
    public array $historyResultsComments = [];
    public $historyExternalServices;
    public bool $showHistoryUploadSections = false;
    public bool $showHistoryUploadForm = false;
    public string $historyUploadType = '';
    public string $historyUploadDate = '';
    public string $historyUploadName = '';
    public string $historyUploadComments = '';
    public $historyUploadFile = null;

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

        $this->refreshHistoryExternalServices();
        $this->initializeHistoryUploadState();
        $this->showHistoryUploadSections = false;
        $this->closeHistoryUploadForm();

        $this->dispatch('open-history-appointment-modal');
    }

    public function toggleHistoryUploadSections(): void
    {
        $this->showHistoryUploadSections = ! $this->showHistoryUploadSections;
    }

    public function openHistoryUploadForm(string $type): void
    {
        $this->resetHistoryUploadForm();
        $this->historyUploadType = $type;
        $this->historyUploadDate = now()->format('Y-m-d');
        $this->showHistoryUploadForm = true;
    }

    public function closeHistoryUploadForm(): void
    {
        $this->resetHistoryUploadForm();
        $this->showHistoryUploadForm = false;
    }

    public function saveHistoryExternalService(): void
    {
        $this->validate([
            'historyUploadName' => 'required|string|max:255',
            'historyUploadDate' => 'required|date',
            'historyUploadComments' => 'nullable|string|max:1000',
            'historyUploadType' => ['required', new Enum(ExternalServicesType::class)],
            'historyUploadFile' => 'max:10240',
        ]);

        if (! $this->historyPatient) {
            return;
        }

        $policy = $this->historyPatient->policy;

        if (! $policy) {
            $this->dispatch('notify',
                type: 'warning',
                content: 'El paciente no tiene membresia activa para registrar archivos.',
                duration: 3500
            );

            return;
        }

        $attachmentPath = null;
        $attachmentName = null;

        if ($this->historyUploadFile) {
            $file = $this->historyUploadFile;
            $attachmentPath = $file->store('external-services');
            $attachmentName = $file->getClientOriginalName();
        }

        PolicyExternalService::create([
            'policy_id' => $policy->id,
            'name' => $this->historyUploadName,
            'date' => $this->historyUploadDate,
            'comments' => $this->historyUploadComments ?: null,
            'type' => $this->historyUploadType,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        $this->refreshHistoryExternalServices();
        $this->closeHistoryUploadForm();

        $this->dispatch('notify',
            type: 'success',
            content: 'Archivo importado exitosamente.',
            duration: 3500
        );
    }

    public function saveHistoryResultsAndKeepPending(int $appointmentId): void
    {
        $this->processHistoryResultUpload($appointmentId, markAsCompleted: false);
    }

    public function saveHistoryResultsAndFinalize(int $appointmentId): void
    {
        $this->processHistoryResultUpload($appointmentId, markAsCompleted: true);
    }

    private function processHistoryResultUpload(int $appointmentId, bool $markAsCompleted): void
    {
        $this->validate([
            "historyServiceAttachments.$appointmentId" => ['required', 'array'],
            "historyServiceAttachments.$appointmentId.*" => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            "historyResultsComments.$appointmentId" => ['nullable', 'string', 'max:2000'],
        ], [
            "historyServiceAttachments.$appointmentId.*.mimes" => 'El archivo debe ser PDF, JPG o PNG.',
            "historyServiceAttachments.$appointmentId.*.max" => 'El archivo no debe superar 2MB.',
            "historyResultsComments.$appointmentId.max" => 'El enlace no debe superar 2000 caracteres.',
        ]);

        $appointment = Appointment::query()
            ->with(['services', 'note'])
            ->where('id', $appointmentId)
            ->where('user_id', $this->historyPatient?->id)
            ->first();

        if (! $appointment) {
            return;
        }

        if (! in_array($appointment->status, [AppointmentStatus::RESULTS_PENDING, AppointmentStatus::COMPLETED], true)) {
            $this->dispatch('notify',
                type: 'warning',
                content: 'La carga de resultados solo esta disponible para consultas atendidas o pendientes de resultados.',
                duration: 3500
            );

            return;
        }

        $completedServiceIds = $appointment->services
            ->where('status', AppointmentStatus::COMPLETED->value)
            ->pluck('id')
            ->all();

        $filesToStore = collect(data_get($this->historyServiceAttachments, $appointmentId, []))
            ->filter(fn ($file) => filled($file));

        $resultsComment = trim((string) data_get($this->historyResultsComments, $appointmentId, ''));
        $hasComment = filled($resultsComment);

        if (! $markAsCompleted && $filesToStore->isEmpty() && ! $hasComment) {
            $this->addError("historyServiceAttachments.$appointmentId", 'Debes seleccionar al menos un archivo o capturar un enlace de resultados.');
            return;
        }

        $appointmentServices = $appointment->services->keyBy('id');

        foreach ($filesToStore as $serviceId => $file) {
            if (! in_array((int) $serviceId, $completedServiceIds, true) || ! isset($appointmentServices[$serviceId])) {
                continue;
            }

            $path = $file->store('attachments');
            $originalName = $file->getClientOriginalName();

            $appointmentServices[$serviceId]->update([
                'attachment_path' => $path,
                'attachment_name' => $originalName,
            ]);
        }

        $appointment->note()->updateOrCreate(
            ['appointment_id' => $appointment->id],
            ['results_comment' => $hasComment ? $resultsComment : null]
        );

        $hasPendingAttachments = AppointmentService::query()
            ->where('appointment_id', $appointment->id)
            ->where('status', AppointmentStatus::COMPLETED->value)
            ->whereNull('attachment_path')
            ->exists();

        if ($markAsCompleted || $appointment->status === AppointmentStatus::COMPLETED) {
            $appointment->update([
                'status' => AppointmentStatus::COMPLETED,
            ]);
        } else {
            $appointment->update([
                'status' => $hasPendingAttachments
                    ? AppointmentStatus::RESULTS_PENDING
                    : AppointmentStatus::COMPLETED,
            ]);
        }

        $this->refreshHistoryAppointments();

        $this->dispatch('notify',
            type: 'success',
            content: 'Resultados guardados correctamente.',
            duration: 3500
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

    private function refreshHistoryExternalServices(): void
    {
        if (! $this->historyPatient?->policy?->id) {
            $this->historyExternalServices = collect();

            return;
        }

        $this->historyExternalServices = PolicyExternalService::query()
            ->where('policy_id', $this->historyPatient->policy->id)
            ->orderByDesc('date')
            ->get();
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
        $this->historyExternalServices = collect();
        $this->showHistoryUploadSections = false;
        $this->closeHistoryUploadForm();
    }

    private function resetHistoryUploadForm(): void
    {
        $this->historyUploadType = '';
        $this->historyUploadDate = now()->format('Y-m-d');
        $this->historyUploadName = '';
        $this->historyUploadComments = '';
        $this->historyUploadFile = null;
    }

    #[On('orderAppointment')]
    public function orderAppointment($appointmentId)
    {
        $appointment = Appointment::findOrFail($appointmentId);

        $pdf = Pdf::loadView('pdf.order', [
            'appointment' => $appointment,
            'contactEmail' => \App\Models\Parameter::where('type', 'RS')->where('key', 'Email')->value('value') ?? 'contacto@inmax.com'
        ])->setPaper('letter', 'portrait');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "order-{$appointment->id}.pdf"
        );
    }
}
