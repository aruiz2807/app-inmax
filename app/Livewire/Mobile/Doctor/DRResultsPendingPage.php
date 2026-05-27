<?php

namespace App\Livewire\Mobile\Doctor;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class DRResultsPendingPage extends Component
{
    use WithFileUploads;

    public $appointments = null;
    public $user;
    public $selectedAppointmentId = null;
    public $selectedAppointmentServices = [];
    public $serviceAttachments = [];
    public bool $showUploadModal = false;
    public bool $isMobileDevice = true;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.results-pending-page');
    }

    public function mount()
    {
        $this->isMobileDevice = $this->detectMobileDevice();
        $this->loadAppointments();
    }

    protected function detectMobileDevice(): bool
    {
        $forcedDevice = request()->query('device');

        if ($forcedDevice === 'mobile') {
            return true;
        }

        if ($forcedDevice === 'desktop') {
            return false;
        }

        $userAgent = strtolower((string) request()->userAgent());

        return preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i', $userAgent) === 1;
    }

    public function loadAppointments(): void
    {
        $this->user = Auth::user();

        $this->appointments = Appointment::with(['user.policy', 'doctor.user', 'office', 'note', 'services.service'])
            ->where('doctor_id', Auth::user()->doctor->id)
            ->where('status', AppointmentStatus::RESULTS_PENDING)
            ->orderByDesc('date')
            ->orderByDesc('time')
            ->get();
    }

    public function openUploadModal(int $appointmentId): void
    {
        $appointment = Appointment::with('services.service')
            ->where('doctor_id', Auth::user()->doctor->id)
            ->findOrFail($appointmentId);

        $this->selectedAppointmentServices = $appointment->services
            ->where('status', 'Completed')
            ->values()
            ->all();

        $this->serviceAttachments = [];
        foreach ($this->selectedAppointmentServices as $service) {
            $this->serviceAttachments[$service->id] = null;
        }

        $this->selectedAppointmentId = $appointmentId;
        $this->showUploadModal = true;
        $this->dispatch('open-upload-results-modal');
    }

    public function closeUploadModal(): void
    {
        $this->resetUploadForm();
        $this->showUploadModal = false;
        $this->dispatch('close-upload-results-modal');
    }

    public function saveResultFile(): void
    {
        $this->validate([
            'selectedAppointmentId' => ['required', 'integer', 'exists:appointments,id'],
            'serviceAttachments' => ['required', 'array'],
            'serviceAttachments.*' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ], [
            'serviceAttachments.*.mimes' => 'El archivo debe ser PDF, JPG o PNG.',
            'serviceAttachments.*.max' => 'El archivo no debe superar 2MB.',
        ]);

        $filesToStore = collect($this->serviceAttachments)
            ->filter(fn ($file) => filled($file));

        if ($filesToStore->isEmpty()) {
            $this->addError('serviceAttachments', 'Debes seleccionar al menos un archivo.');
            return;
        }

        $appointment = Appointment::with('services')
            ->where('doctor_id', Auth::user()->doctor->id)
            ->findOrFail($this->selectedAppointmentId);

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

        $hasPendingAttachments = AppointmentService::query()
            ->where('appointment_id', $appointment->id)
            ->where('status', 'Completed')
            ->whereNull('attachment_path')
            ->exists();

        if (! $hasPendingAttachments) {
            $appointment->update([
                'status' => AppointmentStatus::COMPLETED,
            ]);
        }

        $this->closeUploadModal();
        $this->loadAppointments();
    }

    private function resetUploadForm(): void
    {
        $this->selectedAppointmentId = null;
        $this->selectedAppointmentServices = [];
        $this->serviceAttachments = [];
    }
}