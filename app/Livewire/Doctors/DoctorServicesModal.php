<?php

namespace App\Livewire\Doctors;

use App\Models\Service;
use App\Models\DoctorService;
use Livewire\Component;
use Livewire\Attributes\On;

class DoctorServicesModal extends Component
{
    public ?int $doctorId = null;
    public ?int $serviceId = null;

    public $services = [];
    public $doctorServices = [];

    public function render()
    {
        return view('livewire.doctors.doctor-services-modal');
    }

    public function mount()
    {
        $this->services = Service::query()->where('status', 'Active')->get();
    }

    #[On('editServices')]
    public function editServices($doctorId)
    {
        $this->doctorId = $doctorId;
        $this->serviceId = null;

        $this->loadDoctorServices();

        $this->dispatch('open-doctor-services-modal');
    }

    public function addService()
    {
        if (!$this->serviceId) 
        {
            return;
        }

        DoctorService::create([
            'doctor_id' => $this->doctorId,
            'service_id' => $this->serviceId,
        ]);

        $this->loadDoctorServices();
    }

    public function updateServices()
    {
        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'¡Servicios otorgados almacenados exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-doctor-services-modal');
    }

    public function delete($doctorServiceId)
    {
        DoctorService::whereKey($doctorServiceId)->delete();

        $this->loadDoctorServices();
    }

    private function loadDoctorServices()
    {
        $this->doctorServices = DoctorService::with('service:id,name')
            ->where('doctor_id', $this->doctorId)
            ->get();

        $this->services = Service::query()
            ->where('status', 'Active')
            ->whereDoesntHave('doctorServices', fn ($query) =>
                $query->where('doctor_id', $this->doctorId)
            )
            ->get();
    }
}