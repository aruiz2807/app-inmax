<?php

namespace App\Livewire\Mobile\User;

use App\Enums\DoctorType;
use App\Enums\ExternalServicesType;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\PolicyExternalService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Enum;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

class RecordPage extends Component
{
    use WithFileUploads;

    public $appointments;
    public $exams;
    public $doctorAppointments;
    public $externalServices;

    // Upload form state
    public bool $showUploadForm = false;
    public string $uploadType = '';
    public string $uploadDate = '';
    public string $uploadName = '';
    public string $uploadComments = '';
    public $uploadFile = null;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.record-page');
    }

    public function mount()
    {
        $user = Auth::user();

        $this->appointments = Appointment::where([
            ['user_id', $user->id],
            ['status', \App\Enums\AppointmentStatus::COMPLETED],
        ])->get();

        $this->doctorAppointments  = Appointment::with(['note', 'prescriptions.medication'])
        ->where([
            ['user_id', $user->id],
            ['status', \App\Enums\AppointmentStatus::COMPLETED],
        ])
        ->whereHas('doctor', function ($query) {
            $query->where('type', DoctorType::Doctor);
        })
        ->get();

        $this->exams = AppointmentService::query()
            ->with('appointment')
            ->where('status', \App\Enums\AppointmentStatus::COMPLETED)
            ->whereNotNull('attachment_path')
            ->whereHas('appointment', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', \App\Enums\AppointmentStatus::COMPLETED);
            })
            ->get();

        $policy = $user->policy;
        if ($policy) {
            $this->externalServices = PolicyExternalService::where('policy_id', $policy->id)
                ->orderBy('date', 'desc')
                ->get();
        } else {
            $this->externalServices = collect();
        }

        $this->uploadDate = now()->format('Y-m-d');
    }

    public function openUploadForm(string $type): void
    {
        $this->resetUploadForm();
        $this->uploadType = $type;
        $this->uploadDate = now()->format('Y-m-d');
        $this->showUploadForm = true;
    }

    public function closeUploadForm(): void
    {
        $this->resetUploadForm();
        $this->showUploadForm = false;
    }

    public function saveExternalService(): void
    {
        $this->validate([
            'uploadName'     => 'required|string|max:255',
            'uploadDate'     => 'required|date',
            'uploadComments' => 'nullable|string|max:1000',
            'uploadType'     => ['required', new Enum(ExternalServicesType::class)],
            'uploadFile'     => 'max:10240',
        ]);

        $user = Auth::user();
        $policy = $user->policy;

        $attachmentPath = null;
        $attachmentName = null;

        if ($this->uploadFile) {
            $file = $this->uploadFile;
            $attachmentPath = $file->store('external-services');
            $attachmentName = $file->getClientOriginalName();
        }

        PolicyExternalService::create([
            'policy_id'       => $policy->id,
            'name'            => $this->uploadName,
            'date'            => $this->uploadDate,
            'comments'        => $this->uploadComments ?: null,
            'type'            => $this->uploadType,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        // Reload external services
        $this->externalServices = PolicyExternalService::where('policy_id', $policy->id)
            ->orderBy('date', 'desc')
            ->get();

        $this->resetUploadForm();
        $this->showUploadForm = false;

        $this->dispatch('notify', message: 'Archivo importado exitosamente.');
    }

    private function resetUploadForm(): void
    {
        $this->uploadType     = '';
        $this->uploadDate     = now()->format('Y-m-d');
        $this->uploadName     = '';
        $this->uploadComments = '';
        $this->uploadFile     = null;
    }
}
