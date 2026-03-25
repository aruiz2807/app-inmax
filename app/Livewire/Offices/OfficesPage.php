<?php

namespace App\Livewire\Offices;

use App\Livewire\Forms\OfficesForm;
use App\Models\Office;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class OfficesPage extends Component
{
    public OfficesForm $form;
    public ?int $officeId = null;
    public $specialties = [];
    public $types = [];

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.offices.offices-page');
    }

    public function mount()
    {
        $this->offices = Office::orderBy('name')->get();
    }

    #[On('editOffice')]
    public function edit($officeId)
    {
        $office = Office::find($officeId);

        $this->form->set($office);
        $this->officeId = $officeId;

        //open modal
        $this->dispatch('open-office-modal');
    }

    public function save()
    {
        if($this->officeId)
        {
            $this->form->update($this->officeId);
        }
        else
        {
            $this->form->store();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Consultorio almacenado exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-office-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-officesTable');

        //clear form
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->form->reset();
        $this->officeId = null;
        $this->resetValidation();
    }

}
