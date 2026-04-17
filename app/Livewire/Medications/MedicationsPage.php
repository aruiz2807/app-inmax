<?php

namespace App\Livewire\Medications;

use App\Livewire\Forms\MedicationsForm;
use App\Models\Medication;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class MedicationsPage extends Component
{
    public MedicationsForm $form;
    public ?int $medicationId = null;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.medications.medications-page');
    }

    #[On('editMedication')]
    public function edit($medicationId)
    {
        $medication = Medication::find($medicationId);

        $this->form->set($medication);
        $this->medicationId = $medicationId;

        //open modal
        $this->dispatch('open-medication-modal');
    }

    public function save()
    {
        if($this->medicationId)
        {
            $this->form->update($this->medicationId);
        }
        else
        {
            $this->form->store();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Medicamento almacenado exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-medication-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-medicationsTable');

        //clear form
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->form->reset();
        $this->medicationId = null;
    }
}
