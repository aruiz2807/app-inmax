<?php

namespace App\Livewire\Concerns;

use App\Models\Doctor;
use App\Models\PolicyService;
use App\Models\Service;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

/**
 * Add-new-services functionality for an existing appointment.
 *
 * Adapted from DRSchedulePage (new appointment flow) so the notes and payment
 * pages can also add services to the current appointment.
 *
 * Components using this trait must define:
 *  - $appointment (Appointment, with user.policy and doctor loaded)
 *  - registerNewService(array $serviceData): void  → persists the AppointmentService
 *  - refreshAppointmentServices(): void            → reloads the services list/switches
 */
trait WithAppointmentServiceAdditions
{
    public array $servicesToAdd = [];

    public array $unregisteredServicesToAdd = [];

    public string $newUnregisteredService = '';

    public string $serviceSearch = '';

    public int $servicesLimit = 20;

    public function updatedServiceSearch(): void
    {
        $this->servicesLimit = 20;
    }

    public function loadMoreServices(): void
    {
        $this->servicesLimit += 20;
    }

    public function addUnregisteredService(): void
    {
        $this->validate([
            'newUnregisteredService' => 'required|string|max:255',
        ]);

        $this->unregisteredServicesToAdd[] = $this->newUnregisteredService;
        $this->newUnregisteredService = '';

        $this->dispatch('close-custom-service-modal');
    }

    public function removeUnregisteredService(int $index): void
    {
        unset($this->unregisteredServicesToAdd[$index]);
        $this->unregisteredServicesToAdd = array_values($this->unregisteredServicesToAdd);
    }

    public function updatedServicesToAdd(): void
    {
        $this->syncAddedServices();
    }

    /**
     * Services available to add: the ones associated to the appointment's doctor,
     * or the whole active catalog when the doctor has no associated services.
     */
    #[Computed]
    public function availableServices()
    {
        $doctorId = $this->appointment->doctor_id;

        $doctorServices = $doctorId
            ? Doctor::with('doctorServices.service')
                ->find($doctorId)
                ?->doctorServices
                ->map(fn ($doctorService) => $doctorService->service)
                ->filter()
                ->values() ?? collect()
            : collect();

        // Fallback to the full active catalog when the doctor has no associated services
        $allServices = $doctorServices->isNotEmpty()
            ? $doctorServices
            : Service::where('status', 'Active')->get();

        $existingServiceIds = $this->appointment->services->pluck('service_id')->filter()->all();

        $available = $allServices->reject(fn ($service) => in_array($service->id, $existingServiceIds, true))->values();

        $filtered = $available;
        if ($this->serviceSearch) {
            $normalizedSearch = Str::lower(Str::ascii($this->serviceSearch));

            $filtered = $available->filter(function ($service) use ($normalizedSearch) {
                return Str::contains(
                    Str::lower(Str::ascii($service->name)),
                    $normalizedSearch
                );
            });
        }

        // Always include currently selected services
        $selected = $available->whereIn('id', $this->servicesToAdd);

        return $filtered->take($this->servicesLimit)->merge($selected)->unique('id')->values();
    }

    /**
     * Newly selected services with coverage info for preview display.
     */
    #[Computed]
    public function addedServicesData(): array
    {
        $policy = $this->appointment->user?->policy;

        if (! $policy || (empty($this->servicesToAdd) && empty($this->unregisteredServicesToAdd))) {
            return [];
        }

        $policyId = $policy->type === 'Member' ? $policy->parent_policy_id : $policy->id;

        $data = collect();

        if (! empty($this->servicesToAdd)) {
            $services = Service::whereIn('id', $this->servicesToAdd)->get();

            $data = $services->map(function ($service) use ($policyId) {
                $isCovered = PolicyService::where('policy_id', $policyId)
                    ->where('service_id', $service->id)
                    ->whereColumn('used', '<', 'included')
                    ->exists();

                return [
                    'id' => $service->id,
                    'unregistered_service' => null,
                    'name' => $service->name,
                    'included' => $isCovered,
                ];
            });
        }

        foreach ($this->unregisteredServicesToAdd as $index => $unregistered) {
            $data->push([
                'id' => null,
                'unregistered_service' => $unregistered,
                'name' => $unregistered,
                'included' => false, // Custom services are not covered by default
                'index' => $index,
            ]);
        }

        return $data->toArray();
    }

    /**
     * Persist every newly selected service into the appointment and refresh the list.
     */
    public function syncAddedServices(): void
    {
        foreach ($this->addedServicesData() as $serviceData) {
            $this->registerNewService($serviceData);
        }

        $this->refreshAppointmentServices();

        $this->reset(['servicesToAdd', 'unregisteredServicesToAdd', 'serviceSearch', 'servicesLimit']);
        unset($this->availableServices, $this->addedServicesData);
    }
}
