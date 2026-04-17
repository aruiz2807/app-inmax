<?php

namespace App\Livewire\Forms;

use App\Models\Medication;
use Livewire\Attributes\Validate;
use Livewire\Form;

class MedicationsForm extends Form
{
    #[Validate('required|string|max:6')]
    public $code = '';

    #[Validate('required|string|max:100')]
    public $name = '';

    #[Validate('required|string|max:100')]
    public $trade_name = '';

    #[Validate('required|string|max:100')]
    public $active_substance = '';

    #[Validate('required|string|max:50')]
    public $lab = '';

    #[Validate('required|string|max:100')]
    public $packaging = '';

    #[Validate('required')]
    public $price_public = 0;

    #[Validate('required')]
    public $price_members = 0;

    #[Validate('required')]
    public $status = 'Active';

    /**
    * Store the medication in the DB.
    */
    public function store()
    {
        $this->validate();

        $data = $this->all();

        // Remove thousands separators
        $data['price_public'] = str_replace(',', '', $data['price_public']);
        $data['price_members'] = str_replace(',', '', $data['price_members']);

        Medication::create($data);
    }

    /**
    * Sets the medication to edit.
    */
    public function set(Medication $medication)
    {
        $this->code = $medication->code;
        $this->name = $medication->name;
        $this->trade_name = $medication->trade_name;
        $this->active_substance = $medication->active_substance;
        $this->lab = $medication->lab;
        $this->packaging = $medication->packaging;
        $this->price_public = $medication->price_public;
        $this->price_members = $medication->price_members;
        $this->status = $medication->status;
    }

    /**
    * Updates the medication in the DB.
    */
    public function update($medicationId)
    {
        $this->validate();

        $medication = Medication::find($medicationId);

        $data = $this->all();
        $data['price_public'] = str_replace(',', '', $data['price_public']);
        $data['price_members'] = str_replace(',', '', $data['price_members']);

        $medication->update($data);
    }
}
