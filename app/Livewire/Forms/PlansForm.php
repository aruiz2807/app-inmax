<?php

namespace App\Livewire\Forms;

use App\Models\Plan;
use Livewire\Attributes\Validate;
use Livewire\Form;

class PlansForm extends Form
{
    #[Validate('required|string|max:50')]
    public $name = '';

    #[Validate('required')]
    public $type = 'Individual';

    #[Validate('required')]
    public $price = '';

    /**
    * Store the service in the DB.
    */
    public function store()
    {
        $this->validate();

        $data = $this->only(['name', 'type', 'price']);

        // Remove thousands separators
        $data['price'] = str_replace(',', '', $data['price']);

        Plan::create($data);
    }

    /**
    * Sets the plan to edit.
    */
    public function set(Plan $plan)
    {
        $this->name = $plan->name;
        $this->type = $plan->type;
        $this->price = $plan->price;
    }

    /**
    * Updates the service in the DB.
    */
    public function update($planId)
    {
        $this->validate();

        $plan = Plan::find($planId);

        $plan->update([
            'name' => $this->name,
            'type' => $this->type,
            'price' => str_replace(',', '', $this->price),
        ]);
    }
}
