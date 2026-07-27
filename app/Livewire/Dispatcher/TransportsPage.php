<?php

namespace App\Livewire\Dispatcher;

use App\Models\Office;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class TransportsPage extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'booked';

    public string $patientSearch = '';
    public ?int $selectedPatientId = null;
    public string $transportType = 'programado';
    public array $selectedServiceIds = [];
    public ?string $origin = null;
    public ?string $destinationSelection = null;
    public ?string $customDestinationAddress = null;
    public ?bool $severityConscious = null;
    public ?string $severityBreathing = null;
    public ?bool $severityBleeding = null;
    public ?bool $severityChestPain = null;
    public int $severityPainScale = 0;
    public ?string $notes = null;

    public function mount(): void
    {
        $this->resetForm();
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['booked', 'completed', 'cancelled', 'all'], true)) {
            return;
        }

        $this->tab = $tab;
    }

    public function resetForm(): void
    {
        $this->patientSearch = '';
        $this->selectedPatientId = null;
        $this->transportType = 'programado';
        $this->selectedServiceIds = [];
        $this->origin = null;
        $this->destinationSelection = null;
        $this->customDestinationAddress = null;
        $this->severityConscious = null;
        $this->severityBreathing = null;
        $this->severityBleeding = null;
        $this->severityChestPain = null;
        $this->severityPainScale = 0;
        $this->notes = null;
        $this->resetValidation();
    }

    public function updatedDestinationSelection(): void
    {
        if ($this->destinationSelection !== 'custom') {
            $this->customDestinationAddress = null;
        }
    }

    public function getPatientsProperty(): Collection
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

    public function getSelectedPatientProperty(): ?User
    {
        if (! $this->selectedPatientId) {
            return null;
        }

        return User::query()->with('policy')->find($this->selectedPatientId);
    }

    public function getServicesProperty(): Collection
    {
        return Service::query()
            ->orderBy('name')
            ->get();
    }

    public function getHospitalsProperty(): Collection
    {
        return Office::query()
            ->orderBy('name')
            ->get();
    }

    public function save(): void
    {
        $this->validate([
            'selectedPatientId' => ['required', 'integer', 'exists:users,id'],
            'transportType' => ['required', Rule::in(['programado', 'urgencia', 'cuidados_avanzados'])],
            'selectedServiceIds' => ['required', 'array', 'min:1'],
            'selectedServiceIds.*' => ['integer', 'exists:services,id'],
            'origin' => ['required', 'string', 'max:255'],
            'destinationSelection' => ['required', 'string'],
            'customDestinationAddress' => ['nullable', 'string', 'max:255'],
            'severityConscious' => ['nullable', 'boolean'],
            'severityBreathing' => ['nullable', Rule::in(['no', 'leve', 'grave'])],
            'severityBleeding' => ['nullable', 'boolean'],
            'severityChestPain' => ['nullable', 'boolean'],
            'severityPainScale' => ['required', 'integer', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'selectedPatientId.required' => 'Selecciona un paciente.',
            'selectedServiceIds.required' => 'Selecciona al menos un servicio.',
            'origin.required' => 'Captura el origen del traslado.',
            'destinationSelection.required' => 'Selecciona un destino.',
            'severityPainScale.max' => 'La escala de dolor no puede ser mayor a 10.',
        ]);

        if ($this->destinationSelection === 'custom') {
            $this->validate([
                'customDestinationAddress' => ['required', 'string', 'max:255'],
            ], [
                'customDestinationAddress.required' => 'Captura la dirección del destino.',
            ]);
        }

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Traslado preparado correctamente.',
            duration: 3500
        );

        $this->dispatch('close-transport-modal');
        $this->resetForm();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.dispatcher.transports-page');
    }
}