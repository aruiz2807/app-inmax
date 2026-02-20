<?php

namespace App\Livewire\Forms;

use App\Models\Specialty;
use Livewire\Attributes\Validate;
use Livewire\Form;

class SpecialtiesForm extends Form
{
    #[Validate('required|string|max:100')]
    public $name = '';

    #[Validate('required')]
    public $service_id = '';

    /**
    * Store the service in the DB.
    */
    public function store()
    {
        $this->validate();

        Specialty::create($this->only(['name', 'service_id']));
    }

    /**
    * Sets the service to edit.
    */
    public function set(Specialty $specialty)
    {
        $this->name = $specialty->name;
        $this->service_id = (string) $specialty->service_id;
    }

    /**
    * Updates the service in the DB.
    */
    public function update($specialtyId)
    {
        $this->validate();

        $specialty = Specialty::find($specialtyId);

        $specialty->update([
            'name' => $this->name,
            'service_id' => $this->service_id,
        ]);
    }
}
