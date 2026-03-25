<?php

namespace App\Livewire\Forms;

use App\Models\Doctor;
use App\Models\Office;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Form;

class OfficesForm extends Form
{
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required')]
    public $address = '';

    #[Validate('required|max:2048')]
    public $maps_url = '';

    #[Validate('required')]
    public $office_id = 1;

    protected function rules()
    {
        return [
            
        ];
    }

    /**
    * Store the office in the DB.
    */
    public function store()
    {
        $this->validate();

        Office::create([
            'name' => $this->name,
            'address' => $this->address,
            'maps_url' => $this->maps_url,
        ]);
    }

    /**
    * Sets the office to edit.
    */
    public function set(Office $office)
    {
        $this->name = $office->name;
        $this->address = $office->address;
        $this->maps_url = $office->maps_url;
    }

    /**
    * Updates the office in the DB.
    */
    public function update($officeId)
    {
        //$this->validate();

        $office = Office::find($officeId);

        $office->update([
            'name' => $this->name,
            'address' => $this->address,
            'maps_url' => $this->maps_url,
        ]);
    }
}
