<?php

namespace App\Livewire\Specialties;

use App\Models\Service;
use App\Models\SpecialtyService;
use Livewire\Component;
use Livewire\Attributes\On;

class SpecialtyServicesModal extends Component
{
    public ?int $specialtyId = null;
    public ?int $serviceId = null;

    public $services = [];
    public $specialtyServices = [];

    public function render()
    {
        return view('livewire.specialties.specialty-services-modal');
    }

    public function mount()
    {
        $this->services = Service::query()->where('status', 'Active')->get();
    }

    #[On('editServices')]
    public function editServices($specialtyId)
    {
        $this->specialtyId = $specialtyId;
        $this->serviceId = null;

        $this->loadSpecialtyServices();

        $this->dispatch('open-specialty-services-modal');
    }

    public function addService()
    {
        if (!$this->serviceId) {
            return;
        }

        SpecialtyService::create([
            'specialty_id' => $this->specialtyId,
            'service_id' => $this->serviceId,
        ]);

        $this->loadSpecialtyServices();
    }

    public function updateServices()
    {
        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Servicios otorgados almacenados exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-specialty-services-modal');
    }

    public function delete($specialtyServiceId)
    {
        SpecialtyService::whereKey($specialtyServiceId)->delete();

        $this->loadSpecialtyServices();
    }

    private function loadSpecialtyServices()
    {
        $this->specialtyServices = SpecialtyService::with('service:id,name')
            ->where('specialty_id', $this->specialtyId)
            ->get();

        $this->services = Service::query()
            ->where('status', 'Active')
            ->whereDoesntHave('specialtyServices', fn ($query) =>
                $query->where('specialty_id', $this->specialtyId)
            )
            ->get();
    }
}
