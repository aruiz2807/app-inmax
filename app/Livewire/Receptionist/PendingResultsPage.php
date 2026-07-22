<?php

namespace App\Livewire\Receptionist;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class PendingResultsPage extends Component
{
    use WithFileUploads;

    public ?Appointment $selectedAppointment = null;
    public ?int $selectedAppointmentId = null;
    public array $selectedAppointmentServices = [];
    public array $serviceAttachments = [];
    public ?string $resultsComment = null;
    public int $tableIteration = 0;
    public bool $showUploadModal = false;

    #[On('showReceptionistPendingResultsDetail')]
    public function openDetails(int $appointmentId): void
    {
        $appointment = (clone $this->getBaseQuery())
            ->with([
                'user.policy',
                'doctor.user',
                'doctor.specialty',
                'office',
                'services.service',
            ])
            ->whereKey($appointmentId)
            ->first();

        if (! $appointment) {
            return;
        }

        $this->selectedAppointment = $appointment;
        $this->dispatch('open-receptionist-appointment-detail-modal');
    }

    public function getPendingResultsCountProperty(): int
    {
        return (clone $this->getBaseQuery())->count();
    }

    #[On('showReceptionistPendingResultsUpload')]
    public function openUploadModal(int $appointmentId): void
    {
        $appointment = (clone $this->getBaseQuery())
            ->with(['services.service', 'note'])
            ->whereKey($appointmentId)
            ->first();

        if (! $appointment) {
            return;
        }

        $this->selectedAppointmentServices = $appointment->services
            ->where('status', 'Completed')
            ->values()
            ->all();

        $this->serviceAttachments = [];
        foreach ($this->selectedAppointmentServices as $service) {
            $this->serviceAttachments[$service->id] = null;
        }

        $this->resultsComment = $appointment->note?->results_comment;

        $this->selectedAppointmentId = $appointmentId;
        $this->showUploadModal = true;
        $this->dispatch('open-receptionist-upload-results-modal');
    }

    public function closeUploadModal(): void
    {
        $this->resetUploadForm();
        $this->showUploadModal = false;
        $this->dispatch('close-receptionist-upload-results-modal');
    }

    public function saveAndKeepPending(): void
    {
        $this->processResultUpload(markAsCompleted: false);
    }

    public function saveAndFinalize(): void
    {
        $this->processResultUpload(markAsCompleted: true);
    }

    private function processResultUpload(bool $markAsCompleted): void
    {
        $this->validate([
            'selectedAppointmentId' => ['required', 'integer', 'exists:appointments,id'],
            'serviceAttachments' => ['required', 'array'],
            'serviceAttachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'resultsComment' => ['nullable', 'string', 'max:2000'],
        ], [
            'serviceAttachments.*.mimes' => 'El archivo debe ser PDF, JPG o PNG.',
            'serviceAttachments.*.max' => 'El archivo no debe superar 2MB.',
            'resultsComment.max' => 'El enlace no debe superar 2000 caracteres.',
        ]);

        $filesToStore = collect($this->serviceAttachments)
            ->filter(fn ($file) => filled($file));
        $hasComment = filled($this->resultsComment);

        if (! $markAsCompleted && $filesToStore->isEmpty() && ! $hasComment) {
            $this->addError('serviceAttachments', 'Debes seleccionar al menos un archivo o capturar un enlace de resultados.');
            return;
        }

        $appointment = (clone $this->getBaseQuery())
            ->with('services')
            ->whereKey($this->selectedAppointmentId)
            ->first();

        if (! $appointment) {
            return;
        }

        $appointmentServices = $appointment->services->keyBy('id');

        foreach ($filesToStore as $serviceId => $file) {
            if (! isset($appointmentServices[$serviceId])) {
                continue;
            }

            $path = $file->store('attachments');
            $originalName = $file->getClientOriginalName();

            $appointmentServices[$serviceId]->update([
                'attachment_path' => $path,
                'attachment_name' => $originalName,
            ]);
        }

        if ($hasComment) {
            $appointment->note()->updateOrCreate(
                ['appointment_id' => $appointment->id],
                ['results_comment' => $this->resultsComment]
            );
        }

        $hasPendingAttachments = AppointmentService::query()
            ->where('appointment_id', $appointment->id)
            ->where('status', 'Completed')
            ->whereNull('attachment_path')
            ->exists();

        if ($markAsCompleted) {
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

        $this->closeUploadModal();
        $this->tableIteration++;
    }

    private function resetUploadForm(): void
    {
        $this->selectedAppointmentId = null;
        $this->selectedAppointmentServices = [];
        $this->serviceAttachments = [];
        $this->resultsComment = null;
    }

    private function getBaseQuery(): Builder
    {
        $user = Auth::user();
        if( $user->profile === 'Receptionist' ) {
            $doctorIds = $user->staffDoctors()->pluck('doctors.id');
        } else {
            $doctorIds = $user->doctor()->pluck('doctors.id');
        }

        return Appointment::query()
            ->where(function (Builder $query) use ($doctorIds) {
                $query
                    ->whereIn('appointments.doctor_id', $doctorIds)
                    ->orWhere(function (Builder $officeQuery) use ($doctorIds) {
                        $officeQuery
                            ->whereNull('appointments.doctor_id')
                            ->whereExists(function ($existsQuery) use ($doctorIds) {
                                $existsQuery
                                    ->selectRaw('1')
                                    ->from('office_doctors')
                                    ->whereColumn('office_doctors.office_id', 'appointments.office_id')
                                    ->whereIn('office_doctors.doctor_id', $doctorIds);
                            });
                    });
            })
            ->where('appointments.status', AppointmentStatus::RESULTS_PENDING);
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.receptionist.pending-results-page');
    }
}
