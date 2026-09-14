<?php

namespace App\Livewire\Mobile\Doctor;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\AppointmentPrescription;
use App\Models\Medication;
use App\Models\Parameter;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class DRVirtualNotesPage extends Component
{
    public $user;
    public bool $isMobileDevice = true;

    // Desktop table tabs
    #[Url(as: 'tab')]
    public string $tab = 'pending';
    public ?array $noteDetails = null;

    // Patient selection
    public string $patientSearch = '';
    public ?int $selectedPatientId = null;

    // Medication selection
    public $searchTerm = '';
    public $medicationId = null;
    public $quantity = 1;
    public $dose = '';
    public $frequency = '';
    public $duration = '';
    public array $prescriptions = [];

    public function render()
    {
        $view = $this->isMobileDevice
            ? 'livewire.mobile.doctor.virtual-notes-page'
            : 'livewire.doctor.virtual-notes-page';

        $layout = $this->isMobileDevice ? 'layouts.mobile' : 'layouts.app';

        return view($view)->layout($layout);
    }

    public function resetVirtualForm(): void
    {
        $this->reset(['selectedPatientId', 'patientSearch', 'prescriptions', 'medicationId', 'searchTerm', 'quantity', 'dose', 'frequency', 'duration']);
        $this->resetErrorBag();
    }

    public function mount()
    {
        $this->isMobileDevice = $this->detectMobileDevice();
        $desktopVersionEnabled = Parameter::where('type', 'SITE')->where('key', 'Doctor_VersionDesktop')->first()->value == 'Activa';
        !$desktopVersionEnabled ? $this->isMobileDevice = true : '';

        $this->user = Auth::user();
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

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['all', 'pending', 'partial', 'filled', 'cancelled'], true)) {
            return;
        }

        $this->tab = $tab;
    }

    private function baseVirtualNotesQuery(): Builder
    {
        return Appointment::query()
            ->where('doctor_id', $this->user->doctor->id)
            ->where('status', AppointmentStatus::VIRTUAL->value);
    }

    public function getPendingCountProperty(): int
    {
        return (clone $this->baseVirtualNotesQuery())->where('status_prescription', 'Pending')->count();
    }

    public function getPartialCountProperty(): int
    {
        return (clone $this->baseVirtualNotesQuery())->where('status_prescription', 'Partial')->count();
    }

    public function getFilledCountProperty(): int
    {
        return (clone $this->baseVirtualNotesQuery())->where('status_prescription', 'Filled')->count();
    }

    public function getCancelledCountProperty(): int
    {
        return (clone $this->baseVirtualNotesQuery())->where('status_prescription', 'Cancelled')->count();
    }

    #[On('showVirtualNoteDetails')]
    public function showVirtualNoteDetails(int $appointmentId): void
    {
        $appointment = Appointment::with(['user.policy', 'prescriptions.medication'])
            ->whereKey($appointmentId)
            ->first();

        if (! $appointment) {
            return;
        }

        $this->noteDetails = [
            'patient_name' => $appointment->user?->name ?? 'Sin paciente',
            'membership_number' => $appointment->user?->policy?->number ?? '-',
            'date_label' => $appointment->date?->format('d/m/Y'),
            'prescriptions' => $appointment->prescriptions->map(fn ($prescription) => [
                'name' => $prescription->medication?->name ?? $prescription->description,
                'quantity' => $prescription->quantity,
                'dose' => $prescription->dose,
                'frequency' => $prescription->frequency,
                'duration' => $prescription->duration,
            ])->all(),
        ];

        $this->dispatch('open-virtual-note-details-modal');
    }

    #[Computed]
    public function patients()
    {
        $query = User::query()
            ->with('policy')
            ->whereHas('policy')
            ->when(filled($this->patientSearch), function (Builder $query) {
                $search = trim($this->patientSearch);

                $query->where(function (Builder $innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhereHas('policy', function (Builder $policyQuery) use ($search) {
                            $policyQuery->where('number', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get();

        if ($this->selectedPatientId && ! $query->contains('id', $this->selectedPatientId)) {
            $selectedPatient = User::query()->with('policy')->find($this->selectedPatientId);

            if ($selectedPatient) {
                $query = $query->prepend($selectedPatient);
            }
        }

        return $query->unique('id')->values();
    }

    #[Computed]
    public function selectedPatient()
    {
        if (! $this->selectedPatientId) {
            return null;
        }

        return User::query()->with('policy')->find($this->selectedPatientId);
    }

    public function selectPatient($id)
    {
        $this->selectedPatientId = $id;
        $this->patientSearch = '';
    }

    public function clearPatient()
    {
        $this->selectedPatientId = null;
    }

    #[Computed]
    public function medications()
    {
        if (empty(trim($this->searchTerm))) {
            return collect();
        }

        return Medication::where('status', 'Active')
            ->where(function ($query) {
                $query->where('name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('trade_name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('active_substance', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('packaging', 'like', '%' . $this->searchTerm . '%');
            })
            ->limit(25)
            ->get();
    }

    public function selectMedication($id, $name)
    {
        $this->medicationId = $id;
        $this->searchTerm = $name;
    }

    public function addMedication()
    {
        $this->validate([
            'medicationId' => 'nullable|exists:medications,id',
            'searchTerm' => 'required|string|max:250',
            'quantity' => 'required|integer|max:20',
            'dose' => 'required|string|max:50',
            'frequency' => 'required|string|max:50',
            'duration' => 'required|string|max:50',
        ]);

        $medicationName = $this->medicationId
            ? Medication::find($this->medicationId)?->name
            : null;

        $this->prescriptions[] = [
            'medication_id' => $this->medicationId,
            'name' => $medicationName ?? $this->searchTerm,
            'quantity' => $this->quantity,
            'dose' => $this->dose,
            'frequency' => $this->frequency,
            'duration' => $this->duration,
        ];

        $this->reset(['medicationId', 'quantity', 'dose', 'frequency', 'duration', 'searchTerm']);
    }

    public function removePrescription($index)
    {
        unset($this->prescriptions[$index]);
        $this->prescriptions = array_values($this->prescriptions);
    }

    public function save()
    {
        $this->validate([
            'selectedPatientId' => ['required', 'integer', 'exists:users,id'],
            'prescriptions' => ['required', 'array', 'min:1'],
        ], [
            'selectedPatientId.required' => 'Selecciona un paciente.',
            'prescriptions.required' => 'Agrega al menos un medicamento.',
            'prescriptions.min' => 'Agrega al menos un medicamento.',
        ]);

        DB::transaction(function () {
            $appointment = Appointment::create([
                'user_id' => $this->selectedPatientId,
                'doctor_id' => $this->user->doctor->id,
                'date' => now()->toDateString(),
                'time' => now()->format('H:i:s'),
                'status' => AppointmentStatus::VIRTUAL,
                'status_prescription' => 'Pending',
            ]);

            foreach ($this->prescriptions as $prescription) {
                AppointmentPrescription::create([
                    'appointment_id' => $appointment->id,
                    'medication_id' => $prescription['medication_id'],
                    'description' => $prescription['medication_id'] ? null : $prescription['name'],
                    'quantity' => $prescription['quantity'],
                    'dose' => $prescription['dose'],
                    'frequency' => $prescription['frequency'],
                    'duration' => $prescription['duration'],
                ]);
            }
        });

        $this->reset(['selectedPatientId', 'patientSearch', 'prescriptions', 'medicationId', 'searchTerm', 'quantity', 'dose', 'frequency', 'duration']);

        $this->dispatch('notify',
            type: 'success',
            content: 'Receta virtual generada correctamente.',
            duration: 4000
        );

        if (! $this->isMobileDevice) {
            $this->dispatch('close-virtual-notes-modal');
            $this->dispatch('pg:eventRefresh-virtualNotesTable');

            return;
        }

        return $this->redirectRoute('doctor.home');
    }
}
