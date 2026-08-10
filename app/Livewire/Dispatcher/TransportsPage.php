<?php

namespace App\Livewire\Dispatcher;

use App\Enums\AppointmentStatus;
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
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Carbon;

class TransportsPage extends Component
{
    use WithFileUploads;

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
    public ?Appointment $selectedAppointment = null;
    public ?int $editingAppointmentId = null;
    public bool $isEditing = false;
    public ?Appointment $closingAppointment = null;
    public ?int $closingAppointmentId = null;
    public ?Appointment $cancellingAppointment = null;
    public ?int $cancellingAppointmentId = null;
    public ?string $cancelReason = null;
    public $frapAttachment = null;
    public array $closePerformedServices = [];
    public array $closeAdditionalServices = [];
    public float $closeSubtotal = 0;
    public float $closeDiscount = 0;
    public float $closeUserPayment = 0;
    public float $closeCommission = 0;
    public float $closeProviderTotal = 0;

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

    public function startCreate()
    {
        $this->resetForm();
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
        $this->editingAppointmentId = null;
        $this->isEditing = false;
        $this->resetValidation();
    }

    #[On('dispatcherShowTransportDetail')]
    public function openDetail(int $appointmentId)
    {
        $appointment = $this->managedAppointmentsQuery()
            ->with([
                'user.policy',
                'doctor.user',
                'office',
                'services.service',
            ])
            ->whereKey($appointmentId)
            ->first();

        if (! $appointment) {
            return;
        }

        $this->selectedAppointment = $appointment;
        $this->dispatch('open-transport-detail-modal');
    }

    #[On('dispatcherEditTransport')]
    public function openEdit(int $appointmentId)
    {
        $appointment = $this->managedAppointmentsQuery()
            ->with(['services.service'])
            ->whereKey($appointmentId)
            ->first();

        if (! $appointment || $appointment->status === AppointmentStatus::COMPLETED) {
            return;
        }

        $this->resetForm();
        $this->isEditing = true;
        $this->editingAppointmentId = $appointment->id;

        $this->selectedPatientId = $appointment->user_id;
        $this->origin = $appointment->origin_address;
        $this->notes = $appointment->comments;

        $hospital = $this->hospitals->firstWhere('address', $appointment->destination_address);
        if ($hospital) {
            $this->destinationSelection = (string) $hospital->id;
        } else {
            $this->destinationSelection = 'custom';
            $this->customDestinationAddress = $appointment->destination_address;
        }

        $transportService = $appointment->services->first(fn (AppointmentService $service) => filled($service->service_id));
        $this->selectedTransportServiceId = $transportService?->service_id;

        $severity = is_array($appointment->severity_assessment)
            ? $appointment->severity_assessment
            : json_decode((string) $appointment->severity_assessment, true);

        $this->severityConscious = $severity['conscious'] ?? null;
        $this->severityBreathing = $severity['breathing'] ?? null;
        $this->severityBleeding = $severity['bleeding'] ?? null;
        $this->severityChestPain = $severity['chest_pain'] ?? null;
        $this->severityPainScale = (int) ($severity['pain_scale'] ?? 0);

        $this->hydrateAdditionalItemsFromAppointment($appointment);

        if ($this->isScheduledTransportService) {
            $this->scheduledDate = $appointment->date?->toDateString();
            $this->scheduledTime = $appointment->time?->format('H:i');
        }

        $this->updateSeverityScore();
        $this->dispatch('open-transport-modal');
    }

    #[On('dispatcherCloseTransport')]
    public function closeTransport(int $appointmentId)
    {
        $appointment = $this->managedAppointmentsQuery()
            ->with(['doctor', 'services.service'])
            ->whereKey($appointmentId)
            ->first();

        if (! $appointment || $appointment->status === AppointmentStatus::COMPLETED) {
            return;
        }

        $this->loadCloseSummaryFromAppointment($appointment);
        $this->dispatch('open-transport-close-modal');
    }

    #[On('dispatcherCancelTransport')]
    public function openCancelTransport(int $appointmentId)
    {
        $appointment = $this->managedAppointmentsQuery()
            ->whereKey($appointmentId)
            ->first();

        if (! $appointment || in_array($appointment->status, [AppointmentStatus::COMPLETED, AppointmentStatus::CANCELLED, AppointmentStatus::REJECTED, AppointmentStatus::NO_SHOW], true)) {
            return;
        }

        $this->resetCancelState();
        $this->cancellingAppointment = $appointment;
        $this->cancellingAppointmentId = $appointment->id;
        $this->dispatch('open-transport-cancel-modal');
    }

    public function confirmCancelTransport()
    {
        $this->validate([
            'cancelReason' => ['nullable', 'string', 'max:2000'],
        ], [
            'cancelReason.max' => 'El motivo no puede superar 2000 caracteres.',
        ]);

        if (! $this->cancellingAppointmentId) {
            return;
        }

        $appointment = $this->managedAppointmentsQuery()
            ->whereKey($this->cancellingAppointmentId)
            ->first();

        if (! $appointment || in_array($appointment->status, [AppointmentStatus::COMPLETED, AppointmentStatus::CANCELLED, AppointmentStatus::REJECTED, AppointmentStatus::NO_SHOW], true)) {
            return;
        }

        $currentComments = trim((string) $appointment->comments);
        $formattedReason = trim((string) $this->cancelReason);

        $newComments = $currentComments !== ''
            ? rtrim($currentComments) . PHP_EOL . 'Cancelado por: ' . $formattedReason
            : 'Cancelado por: ' . $formattedReason;

        $appointment->update([
            'status' => AppointmentStatus::CANCELLED,
            'comments' => $newComments,
        ]);

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Traslado cancelado correctamente.',
            duration: 3500
        );

        $this->dispatch('close-transport-cancel-modal');
        $this->dispatch('pg:eventRefresh-dispatcherTransportsTable');
        $this->resetCancelState();
    }

    public function resetCancelState()
    {
        $this->cancellingAppointment = null;
        $this->cancellingAppointmentId = null;
        $this->cancelReason = null;
        $this->resetValidation();
    }

    public function finalizeCloseTransport()
    {
        $this->validate([
            'frapAttachment' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
        ], [
            'frapAttachment.required' => 'Adjunta el FRAP para finalizar el traslado.',
            'frapAttachment.mimes' => 'El FRAP debe ser PDF, JPG o PNG.',
            'frapAttachment.max' => 'El FRAP no debe superar 4MB.',
        ]);

        if (! $this->closingAppointmentId) {
            return;
        }

        $appointment = $this->managedAppointmentsQuery()
            ->with(['doctor', 'services.service'])
            ->whereKey($this->closingAppointmentId)
            ->first();

        if (! $appointment || $appointment->status === AppointmentStatus::COMPLETED) {
            return;
        }

        $attachmentPath = $this->frapAttachment->store('attachments');
        $attachmentName = $this->frapAttachment->getClientOriginalName();

        $serviceForFrap = $appointment->services()
            ->whereNotNull('service_id')
            ->orderBy('id')
            ->first() ?? $appointment->services()->orderBy('id')->first();

        if (! $serviceForFrap) {
            $this->dispatch(
                'notify',
                type: 'error',
                content: 'No se encontró un servicio para asociar el FRAP.',
                duration: 3500
            );

            return;
        }

        $serviceForFrap->update([
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
        ]);

        $this->redeemPolicyServicesForTransport($appointment);

        $appointment->update([
            'status' => AppointmentStatus::COMPLETED,
            'subtotal' => $this->closeSubtotal,
            'coupon_discount' => $this->closeDiscount,
            'user_payment' => $this->closeUserPayment,
            'commission' => $this->closeCommission,
            'total' => $this->closeProviderTotal,
        ]);

        if ($this->selectedAppointment?->id === $appointment->id) {
            $this->selectedAppointment = $appointment->fresh(['user.policy', 'doctor.user', 'office', 'services.service']);
        }

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Traslado cerrado correctamente.',
            duration: 3500
        );

        $this->dispatch('close-transport-close-modal');
        $this->dispatch('pg:eventRefresh-dispatcherTransportsTable');
        $this->resetCloseState();
    }

    public function resetCloseState()
    {
        $this->closingAppointment = null;
        $this->closingAppointmentId = null;
        $this->frapAttachment = null;
        $this->closePerformedServices = [];
        $this->closeAdditionalServices = [];
        $this->closeSubtotal = 0;
        $this->closeDiscount = 0;
        $this->closeUserPayment = 0;
        $this->closeCommission = 0;
        $this->closeProviderTotal = 0;
    }

    private function loadCloseSummaryFromAppointment(Appointment $appointment)
    {
        $this->resetCloseState();
        $this->closingAppointment = $appointment;
        $this->closingAppointmentId = $appointment->id;

        foreach ($appointment->services as $service) {
            if ($service->service_id) {
                $this->closePerformedServices[] = [
                    'name' => $service->service?->name ?? 'Servicio',
                    'amount' => (float) ($service->service?->price ?? 0),
                    'covered' => (bool) $service->covered,
                ];
                continue;
            }

            if (! filled($service->unregistered_service)) {
                continue;
            }

            $rawText = (string) $service->unregistered_service;
            $this->closeAdditionalServices[] = [
                'name' => trim((string) preg_replace('/\s*-\s*\$[\d,.]+$/', '', $rawText)),
                'amount' => $this->extractAmountFromUnregisteredService($rawText),
                'covered' => (bool) $service->covered,
            ];
        }

        [$this->closeSubtotal, $servicesIncludedTotal] = $this->calculateTransportSubtotal($appointment);
        $subtotal = round($this->closeSubtotal - $servicesIncludedTotal, 2);

        $doctorDiscount = ((float) ($appointment->doctor?->discount ?? 0)) / 100;
        $doctorCommission = ((float) ($appointment->doctor?->commission ?? 0)) / 100;

        if ($servicesIncludedTotal > 0) {
            $commission = round($servicesIncludedTotal * $doctorCommission, 2);

            $this->closeCommission = (-$servicesIncludedTotal) - $commission;
            $this->closeProviderTotal = $subtotal+$servicesIncludedTotal - $commission;
            $this->closeSubtotal = $subtotal;
            
        } else {
            $this->closeCommission = round($this->closeSubtotal * $doctorCommission, 2);
            $this->closeProviderTotal = ($this->closeSubtotal - $this->closeDiscount - $this->closeCommission);
        }

        $this->closeDiscount = round($subtotal * $doctorDiscount, 2);
        $this->closeUserPayment = ($this->closeSubtotal - $this->closeDiscount);
        
    }

    private function redeemPolicyServicesForTransport(Appointment $appointment)
    {
        $policy = $appointment->user?->policy;

        if (! $policy) {
            return;
        }

        $policyId = $policy->type === 'Member'
            ? $policy->parent_policy_id
            : $policy->id;

        foreach ($appointment->services as $service) {
            if (! $service->service_id) {
                continue;
            }

            $benefit = PolicyService::query()
                ->where('policy_id', $policyId)
                ->where('service_id', $service->service_id)
                ->orderByRaw('used < included DESC')
                ->first();

            if (! $benefit) {
                continue;
            }

            if ($benefit->used < $benefit->included) {
                $benefit->increment('used');
            } else {
                $benefit->increment('extra');
            }
        }
    }

    private function calculateTransportSubtotal(Appointment $appointment)
    {
        $subtotal = 0;
        $servicesIncludedTotal = 0;

        foreach ($appointment->services as $service) {

            if ($service->covered) {
                $servicesIncludedTotal += (float) ($service->service?->price ?? 0);
            }

            if ($service->service_id) {
                $subtotal += (float) ($service->service?->price ?? 0);
                continue;
            }

            if (! filled($service->unregistered_service)) {
                continue;
            }

            $subtotal += $this->extractAmountFromUnregisteredService((string) $service->unregistered_service);
        }

        return [round($subtotal, 2), round($servicesIncludedTotal, 2)];
    }

    private function extractAmountFromUnregisteredService(string $text)
    {
        if (! preg_match('/\$\s*([\d,]+(?:\.\d{1,2})?)/', $text, $matches)) {
            return 0;
        }

        return (float) str_replace(',', '', $matches[1]);
    }

    private function hydrateAdditionalItemsFromAppointment(Appointment $appointment)
    {
        $this->includeSupplies = false;
        $this->selectedSupplyItems = [];
        $this->includeStairManeuvers = false;
        $this->includeSceneWait = false;
        $this->sceneWaitHours = 1;

        $supplyMap = collect($this->supplyCatalog)
            ->mapWithKeys(fn (array $item, string $key) => [Str::lower($item['label']) => $key]);

        $additionalServices = $appointment->services
            ->whereNull('service_id')
            ->filter(fn (AppointmentService $service) => filled($service->unregistered_service));

        foreach ($additionalServices as $additionalService) {
            $serviceName = trim((string) $additionalService->unregistered_service);
            $baseName = trim((string) preg_replace('/\s*-\s*\$[\d,.]+$/', '', $serviceName));
            $normalizedBaseName = Str::lower(Str::ascii($baseName));

            if ($supplyMap->has($normalizedBaseName)) {
                $this->includeSupplies = true;
                $this->selectedSupplyItems[] = $supplyMap->get($normalizedBaseName);
                continue;
            }

            if (Str::contains($normalizedBaseName, 'maniobras de ascenso/descenso')) {
                $this->includeStairManeuvers = true;
                continue;
            }

            if (Str::contains($normalizedBaseName, 'tiempo de espera en escena')) {
                $this->includeSceneWait = true;

                if (preg_match('/\((\d+)\s*h\)/i', $baseName, $matches)) {
                    $this->sceneWaitHours = (int) max(1, (int) ($matches[1] ?? 1));
                }
            }
        }

        $this->selectedSupplyItems = collect($this->selectedSupplyItems)
            ->unique()
            ->values()
            ->all();
    }

    private function managedAppointmentsQuery()
    {
        $doctorIds = Auth::user()->staffDoctors()->pluck('doctors.id');

        return Appointment::query()->whereIn('doctor_id', $doctorIds);
    }

    private function resolveDestinationAddress()
    {
        if ($this->destinationSelection === 'custom') {
            return $this->customDestinationAddress;
        }

        $hospital = $this->hospitals->firstWhere('id', (int) $this->destinationSelection);

        return $hospital?->address ?? $this->customDestinationAddress;
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
        $selectedTransportServiceId = (int) ($this->selectedTransportServiceId ?? 0);
        $selectedTransportData = $this->transportServicesData
            ->first(fn (array $item) => (int) $item['service']->id === $selectedTransportServiceId);

        $isTransportIncluded = (bool) ($selectedTransportData['included'] ?? false);

        $transportPrice = $this->selectedTransportService && ! $isTransportIncluded
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
            'scheduledDate' => [Rule::requiredIf($this->isScheduledTransportService), 'nullable', 'date', 'after_or_equal:today'],
            'scheduledTime' => [Rule::requiredIf($this->isScheduledTransportService), 'nullable', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'selectedPatientId.required' => 'Selecciona un paciente.',
            'selectedTransportServiceId.required' => 'Selecciona un tipo de traslado.',
            'origin.required' => 'Captura el origen del traslado.',
            'destinationSelection.required' => 'Selecciona un destino.',
            'severityPainScale.max' => 'La escala de dolor no puede ser mayor a 10.',
            'scheduledDate.required' => 'Selecciona la fecha del traslado programado.',
            'scheduledDate.after_or_equal' => 'La fecha del traslado no puede ser anterior a hoy.',
            'scheduledTime.required' => 'Selecciona la hora del traslado programado.',
        ]);

        if ($this->isScheduledTransportService && filled($this->scheduledDate) && filled($this->scheduledTime)) {
            $scheduledAt = Carbon::createFromFormat('Y-m-d H:i', $this->scheduledDate . ' ' . $this->scheduledTime);

            if ($scheduledAt->lt(now())) {
                $this->addError('scheduledTime', 'La fecha y hora programadas no pueden ser menores al momento actual.');
                return;
            }
        }

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

        $destinationAddress = $this->resolveDestinationAddress();

        $appointmentData = [
            'user_id' => $this->selectedPatientId,
            'requested_by_user_id' => Auth::user()->id,
            'date' => $appointmentDate,
            'time' => $appointmentTime,
            'status' => $hasScheduledFields
                ? AppointmentStatus::REQUESTED
                : AppointmentStatus::BOOKED,
            'origin_address' => $this->origin,
            'destination_address' => $destinationAddress,
            'severity_assessment' => json_encode([
                'conscious' => $this->severityConscious,
                'breathing' => $this->severityBreathing,
                'bleeding' => $this->severityBleeding,
                'chest_pain' => $this->severityChestPain,
                'pain_scale' => $this->severityPainScale,
            ]),
            'comments' => $this->notes,
            'edited' => $this->isEditing,
        ];

        $appointment = null;

        if ($this->isEditing && $this->editingAppointmentId) {
            $appointment = $this->managedAppointmentsQuery()->whereKey($this->editingAppointmentId)->first();

            if (! $appointment || $appointment->status === AppointmentStatus::COMPLETED) {
                return;
            }

            $appointment->update($appointmentData);
        } else {
            $appointmentData['doctor_id'] = Auth::user()->staffDoctors()->first()?->id;
            $appointment = Appointment::create($appointmentData);
        }

        $appointment->services()->delete();

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
            content: $this->isEditing ? 'Traslado actualizado correctamente.' : 'Traslado preparado correctamente.',
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