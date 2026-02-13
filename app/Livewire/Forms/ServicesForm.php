<?php

namespace App\Livewire\Forms;

use App\Models\Service;
use Livewire\Attributes\Validate;
use Livewire\Form;

class ServicesForm extends Form
{
    #[Validate('required|string|max:100')]
    public $name = '';

    #[Validate('required')]
    public $type = 'Event';

    /**
    * Store the service in the DB.
    */
    public function store()
    {
        $this->validate();

        Service::create($this->only(['name', 'type']));
    }

    /**
    * Sets the service to edit.
    */
    public function set(Service $service)
    {
        $this->name = $service->name;
        $this->type = $service->type;
    }

    /**
    * Updates the service in the DB.
    */
    public function update($serviceId)
    {
        $this->validate();

        $service = Service::find($serviceId);

        $service->update([
            'name' => $this->name,
            'type' => $this->type,
        ]);
    }
}
