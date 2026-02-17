<?php

namespace App\Livewire\Doctors;

use App\Livewire\Forms\DoctorsForm;
use App\Models\Doctor;
use App\Models\Specialty;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class DoctorsPage extends Component
{
    public DoctorsForm $form;
    public ?int $doctorId = null;
    public $specialties = [];

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.doctors.doctors-page');
    }

    public function mount()
    {
        $this->specialties = Specialty::orderBy('name')->get();
    }

    #[On('editDoctor')]
    public function edit($doctorId)
    {
        $doctor = Doctor::find($doctorId);

        $this->form->set($doctor);
        $this->doctorId = $doctorId;

        //open modal
        $this->dispatch('open-doctor-modal');
    }

    public function save()
    {
        if($this->doctorId)
        {
            $this->form->update($this->doctorId);
        }
        else
        {
            $this->form->store();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Medico almacenado exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-doctor-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-doctorsTable');

        //clear form
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->form->reset();
        $this->doctorId = null;
    }
}
