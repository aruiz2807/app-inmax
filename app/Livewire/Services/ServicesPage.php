<?php

namespace App\Livewire\Services;

use App\Livewire\Forms\ServicesForm;
use App\Models\Service;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class ServicesPage extends Component
{
    public ServicesForm $form;
    public ?int $serviceId = null;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.services.services-page');
    }

    #[On('editService')]
    public function edit($serviceId)
    {
        $service = Service::find($serviceId);

        $this->form->set($service);
        $this->serviceId = $serviceId;

        //open modal
        $this->dispatch('open-service-modal');
    }

    public function save()
    {
        if($this->serviceId)
        {
            $this->form->update($this->serviceId);
        }
        else
        {
            $this->form->store();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Servicio almacenado exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-service-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-servicesTable');

        //clear form
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->form->reset();
        $this->serviceId = null;
    }
}
