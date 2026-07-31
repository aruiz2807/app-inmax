<?php

namespace App\Livewire\Dispatcher;

use App\Models\Doctor;
use App\Models\Service;
use App\Models\User;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\PolicyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Illuminate\Support\Carbon;

class TransportsPage extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'booked';

    public string $patientSearch = '';
    public ?int $selectedPatientId = null;
    public ?int $selectedTransportServiceId = null;
    public bool $includeSupplies = false;
    public array $selectedSupplyItems = [];
    public bool $includeStairManeuvers = false;
    public bool $includeSceneWait = false;
    public int $sceneWaitHours = 1;
    public ?string $origin = null;
    public ?string $destinationSelection = null;
    public ?string $customDestinationAddress = null;
    public ?bool $severityConscious = null;
    public ?string $severityBreathing = null;
    public ?bool $severityBleeding = null;
    public ?bool $severityChestPain = null;
    public int $severityPainScale = 0;
    public ?string $scheduledDate = null;
    public ?string $scheduledTime = null;
    public ?string $notes = null;
    public int $severityScore = 0;
    public string $severityLevel = 'Sin evaluar';
    public string $severityColor = 'slate';

    public function mount()
    {
        $this->tab = $this->normalizeTab($this->tab);
        $this->resetForm();
    }

    public function setTab(string $tab)
    {
        $normalizedTab = $this->normalizeTab($tab);

        if (! in_array($normalizedTab, ['booked', 'in_progress', 'completed', 'cancelled', 'all'], true)) {
            return;
        }

        $this->tab = $normalizedTab;
    }

    private function normalizeTab(string $tab)
    {
        return match ($tab) {
            'inProgress' => 'in_progress',
            default => $tab,
        };
    }

    public function resetForm()
    {
        $this->patientSearch = '';
        $this->selectedPatientId = null;
        $this->selectedTransportServiceId = null;
        $this->includeSupplies = false;
        $this->selectedSupplyItems = [];
        $this->includeStairManeuvers = false;
        $this->includeSceneWait = false;
        $this->sceneWaitHours = 1;
        $this->origin = null;
        $this->destinationSelection = null;
        $this->customDestinationAddress = null;
        $this->severityConscious = null;
        $this->severityBreathing = null;
        $this->severityBleeding = null;
        $this->severityChestPain = null;
        $this->severityPainScale = 0;
        $this->scheduledDate = null;
        $this->scheduledTime = null;
        $this->notes = null;
        $this->severityScore = 0;
        $this->severityLevel = 'Sin evaluar';
        $this->severityColor = 'slate';
        $this->resetValidation();
    }

    public function updateSeverityScore()
    {
        $score = 0;

        if ($this->severityConscious === false) {
            $score += 3;
        } else {
            $score += 1;
        }

        if ($this->severityBreathing === 'grave') {
            $score += 3;
        } elseif ($this->severityBreathing === 'leve') {
            $score += 1;
        }

        if ($this->severityBleeding === true) {
            $score += 2;
        }

        if ($this->severityChestPain === true) {
            $score += 2;
        }

        $score += (int) round($this->severityPainScale/4);

        $this->severityScore = $score;
        
        if ($score == 0) {
            $this->severityLevel = 'Sin evaluar';
            $this->severityColor = 'slate';
        } elseif ($score >= 5) {
            $this->severityLevel = 'Crítica';
            $this->severityColor = 'red';
        } elseif ($score >= 2) {
            $this->severityLevel = 'Moderada';
            $this->severityColor = 'yellow';
        } else {
            $this->severityLevel = 'Estable';
            $this->severityColor = 'green';
        }
    }

    public function updated($property)
    {
        if (in_array($property, [
            'severityConscious',
            'severityBreathing',
            'severityBleeding',
            'severityChestPain',
            'severityPainScale',
        ])) {
            $this->updateSeverityScore();
        }
    }

    public function updatedSelectedTransportServiceId()
    {
        if (! $this->isScheduledTransportService) {
            $this->scheduledDate = null;
            $this->scheduledTime = null;
        }
    }

    public function updatedIncludeSupplies($value)
    {
        if (! (bool) $value) {
            $this->selectedSupplyItems = [];
        }
    }

    public function updatedIncludeSceneWait($value)
    {
        if (! (bool) $value) {
            $this->sceneWaitHours = 1;
        }
    }

    public function incrementSceneWaitHours()
    {
        $this->sceneWaitHours = min(24, $this->sceneWaitHours + 1);
    }

    public function decrementSceneWaitHours()
    {
        $this->sceneWaitHours = max(1, $this->sceneWaitHours - 1);
    }

    public function updatedDestinationSelection()
    {
        if ($this->destinationSelection !== 'custom') {
            $this->customDestinationAddress = null;
        }
    }

    public function getPatientsProperty()
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

    public function getSelectedPatientProperty()
    {
        if (! $this->selectedPatientId) {
            return null;
        }

        return User::query()->with('policy')->find($this->selectedPatientId);
    }

    public function getServicesProperty()
    {
        $me = Auth::user();
        
        return $me->staffDoctors()
            ->with('doctorServices.service:id,name,price')
            ->get()
            ->pluck('doctorServices')
            ->flatten()
            ->pluck('service')
            ->flatten()
            ->unique('id')
            ->sortBy('id')
            ->values();
    }

    public function getSupplyCatalogProperty()
    {
        return [
            'oxygen' => ['label' => 'Oxigeno suplementario', 'price' => 350],
            'iv' => ['label' => 'Solucion IV / electrolitos', 'price' => 180],
            'bandages' => ['label' => 'Vendajes y ferulas', 'price' => 220],
            'vasopressors' => ['label' => 'Vasopresores', 'price' => 600],
            'controlled-meds' => ['label' => 'Medicamentos controlados', 'price' => 450],
        ];
    }

    public function getSelectedTransportServiceProperty()
    {
        if (! $this->selectedTransportServiceId) {
            return null;
        }

        return $this->services->firstWhere('id', (int) $this->selectedTransportServiceId);
    }

    public function getTransportServicesDataProperty()
    {
        $policy = $this->selectedPatient?->policy;

        if (! $policy) {
            return $this->services->map(fn (Service $service) => [
                'service' => $service,
                'included' => false,
            ])->values();
        }

        $policyId = $policy->type === 'Member'
            ? $policy->parent_policy_id
            : $policy->id;

        $includedServiceIds = PolicyService::query()
            ->where('policy_id', $policyId)
            ->whereIn('service_id', $this->services->pluck('id')->all())
            ->whereColumn('used', '<', 'included')
            ->pluck('service_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $this->services->map(function (Service $service) use ($includedServiceIds) {
            return [
                'service' => $service,
                'included' => in_array((int) $service->id, $includedServiceIds, true),
            ];
        })->values();
    }

    public function getIsScheduledTransportServiceProperty()
    {
        $serviceName = $this->selectedTransportService?->name;

        if (! $serviceName) {
            return false;
        }

        $normalizedName = Str::lower(Str::ascii($serviceName));

        return Str::contains($normalizedName, 'programado');
    }

    public function getBudgetSubtotalProperty()
    {
        $transportPrice = $this->selectedTransportService
            ? (float) $this->selectedTransportService->price
            : 0;

        $additionalPrice = $this->additionalExtrasSubtotal;

        return (float) ($transportPrice + $additionalPrice);
    }

    public function getSelectedSupplyItemsSubtotalProperty()
    {
        if (! $this->includeSupplies) {
            return 0;
        }

        return (float) collect($this->selectedSupplyItems)
            ->sum(fn (string $key) => (float) ($this->supplyCatalog[$key]['price'] ?? 0));
    }

    public function getAdditionalExtrasSubtotalProperty()
    {
        $subtotal = 0;

        if ($this->includeSupplies) {
            $subtotal += $this->selectedSupplyItemsSubtotal;
        }

        if ($this->includeStairManeuvers) {
            $subtotal += 250;
        }

        if ($this->includeSceneWait) {
            $subtotal += ($this->sceneWaitHours * 300);
        }

        return (float) $subtotal;
    }

    public function getSelectedAdditionalItemsProperty()
    {
        $items = collect();

        if ($this->includeSupplies) {
            foreach ($this->selectedSupplyItems as $key) {
                $item = $this->supplyCatalog[$key] ?? null;

                if ($item) {
                    $items->push([
                        'name' => $item['label'],
                        'amount' => (float) $item['price'],
                    ]);
                }
            }
        }

        if ($this->includeStairManeuvers) {
            $items->push([
                'name' => 'Maniobras de ascenso/descenso',
                'amount' => 250,
            ]);
        }

        if ($this->includeSceneWait) {
            $items->push([
                'name' => 'Tiempo de espera en escena (' . $this->sceneWaitHours . ' h)',
                'amount' => (float) ($this->sceneWaitHours * 300),
            ]);
        }

        return $items;
    }

    public function getFormattedBudgetSubtotalProperty()
    {
        return '$' . number_format($this->budgetSubtotal, 2);
    }


    public function getHospitalsProperty()
    {
        return Doctor::query()
            ->where([
                ['business_name', 'like', '%Hospital%'],
                ['status', '=', 'active'],
                ['type', 'Provider']
            ])
            ->orderBy('business_name')
            ->get();
    }

    public function save()
    {
        $this->validate([
            'selectedPatientId' => ['required', 'integer', 'exists:users,id'],
            'selectedTransportServiceId' => ['required', 'integer', 'exists:services,id'],
            'includeSupplies' => ['boolean'],
            'selectedSupplyItems' => ['array'],
            'selectedSupplyItems.*' => ['string', Rule::in(array_keys($this->supplyCatalog))],
            'includeStairManeuvers' => ['boolean'],
            'includeSceneWait' => ['boolean'],
            'sceneWaitHours' => ['required_if:includeSceneWait,1', 'integer', 'min:1', 'max:24'],
            'origin' => ['required', 'string', 'max:255'],
            'destinationSelection' => ['required', 'string'],
            'customDestinationAddress' => ['nullable', 'string', 'max:255'],
            'severityConscious' => ['nullable', 'boolean'],
            'severityBreathing' => ['nullable', Rule::in(['no', 'leve', 'grave'])],
            'severityBleeding' => ['nullable', 'boolean'],
            'severityChestPain' => ['nullable', 'boolean'],
            'severityPainScale' => ['required', 'integer', 'min:0', 'max:10'],
            'scheduledDate' => [Rule::requiredIf($this->isScheduledTransportService), 'nullable', 'date'],
            'scheduledTime' => [Rule::requiredIf($this->isScheduledTransportService), 'nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'selectedPatientId.required' => 'Selecciona un paciente.',
            'selectedTransportServiceId.required' => 'Selecciona un tipo de traslado.',
            'origin.required' => 'Captura el origen del traslado.',
            'destinationSelection.required' => 'Selecciona un destino.',
            'severityPainScale.max' => 'La escala de dolor no puede ser mayor a 10.',
            'scheduledDate.required' => 'Selecciona la fecha del traslado programado.',
            'scheduledTime.required' => 'Selecciona la hora del traslado programado.',
        ]);

        $selectedTransportServiceId = (int) $this->selectedTransportServiceId;

        if ($this->destinationSelection === 'custom') {
            $this->validate([
                'customDestinationAddress' => ['required', 'string', 'max:255'],
            ], [
                'customDestinationAddress.required' => 'Captura la dirección del destino.',
            ]);
        }

        $selectedTransportData = $this->transportServicesData
            ->first(fn (array $item) => (int) $item['service']->id === $selectedTransportServiceId);

        $selectedTransportServiceIncluded = (bool) ($selectedTransportData['included'] ?? false);
        $hasScheduledFields = filled($this->scheduledDate) && filled($this->scheduledTime);
        $appointmentDate = $hasScheduledFields
            ? $this->scheduledDate
            : Carbon::now()->toDateString();
        $appointmentTime = $hasScheduledFields
            ? Carbon::createFromFormat('H:i', $this->scheduledTime)->format('H:i:s')
            : Carbon::now()->toTimeString();

        $appointment = Appointment::create([
            'user_id' => $this->selectedPatientId,
            'doctor_id' => Auth::user()->staffDoctors()->first()->id,
            'requested_by_user_id' => Auth::user()->id,
            'date' => $appointmentDate,
            'time' => $appointmentTime,
            'status' => $hasScheduledFields
                ? \App\Enums\AppointmentStatus::REQUESTED
                : \App\Enums\AppointmentStatus::BOOKED,
            'origin_address' => $this->origin,
            'destination_address' => $this->destinationSelection === 'custom' ? $this->customDestinationAddress : Doctor::find($this->destinationSelection)->address,
            'severity_assessment' => json_encode([
                'conscious' => $this->severityConscious,
                'breathing' => $this->severityBreathing,
                'bleeding' => $this->severityBleeding,
                'chest_pain' => $this->severityChestPain,
                'pain_scale' => $this->severityPainScale,
            ]),
            'comments' => $this->notes,
        ]);

        AppointmentService::create([
            'appointment_id' => $appointment->id,
            'service_id' => $selectedTransportServiceId,
            'unregistered_service' => null,
            'covered' => $selectedTransportServiceIncluded,
        ]);

        foreach ($this->selectedAdditionalItems as $item) {
            AppointmentService::create([
                'appointment_id' => $appointment->id,
                'service_id' => null,
                'unregistered_service' => $item['name'] . ' - $' . number_format((float) $item['amount'], 2),
                'covered' => false,
            ]);
        }

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Traslado preparado correctamente.',
            duration: 3500
        );

        $this->dispatch('pg:eventRefresh-dispatcherTransportsTable');
        $this->dispatch('close-transport-modal');
        $this->resetForm();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.dispatcher.transports-page');
    }
}