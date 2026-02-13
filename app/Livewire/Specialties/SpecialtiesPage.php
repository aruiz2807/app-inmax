<?php

namespace App\Livewire\Specialties;

use App\Livewire\Forms\SpecialtiesForm;
use App\Models\Specialty;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class SpecialtiesPage extends Component
{
    public SpecialtiesForm $form;
    public ?int $specialtyId = null;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.specialties.specialties-page');
    }

    #[On('editSpecialty')]
    public function edit($specialtyId)
    {
        $specialty = Specialty::find($specialtyId);

        $this->form->set($specialty);
        $this->specialtyId = $specialtyId;

        //open modal
        $this->dispatch('open-specialty-modal');
    }

    public function save()
    {
        if($this->specialtyId)
        {
            $this->form->update($this->specialtyId);
        }
        else
        {
            $this->form->store();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Especialidad almacenada exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-specialty-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-specialtiesTable');

        //clear form
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->form->reset();
        $this->specialtyId = null;
    }
}
