<?php

namespace App\Livewire\Forms;

use App\Models\Coupon;
use App\Models\Service;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CouponsForm extends Form
{
    #[Validate('required|string|max:200')]
    public $name = '';

    #[Validate('required')]
    public $type = 'Amount';

    #[Validate('required')]
    public $value = '0';

    public $service = null;

    /**
    * Store the service in the DB.
    */
    public function store()
    {
        $this->validate();

        Coupon::create([
            'service_id' => $this->service,
            'name' => $this->name,
            'type' => $this->type,
            'value' => str_replace(',', '', $this->value),
        ]);
    }

    /**
    * Sets the service to edit.
    */
    public function set(Coupon $coupon)
    {
        $this->service = $coupon->service_id;
        $this->name = $coupon->name;
        $this->type = $coupon->type;
        $this->value = $coupon->value;
    }

    /**
    * Updates the service in the DB.
    */
    public function update($couponId)
    {
        $this->validate();

        $coupon = Coupon::find($couponId);

        $coupon->update([
            'service_id' => $this->service,
            'name' => $this->name,
            'type' => $this->type,
            'value' => str_replace(',', '', $this->value),
        ]);
    }
}
