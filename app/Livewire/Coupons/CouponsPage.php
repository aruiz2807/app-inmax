<?php

namespace App\Livewire\Coupons;

use App\Livewire\Forms\CouponsForm;
use App\Models\Coupon;
use App\Models\Service;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class CouponsPage extends Component
{
    public CouponsForm $form;
    public ?int $couponId = null;
    public $services = [];

    public function mount()
    {
        $this->services = Service::query()->where('status', 'Active')->get();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.coupons.coupons-page');
    }

    #[On('editCoupon')]
    public function edit($couponId)
    {
        $coupon = Coupon::find($couponId);

        $this->form->set($coupon);
        $this->couponId = $couponId;

        //open modal
        $this->dispatch('open-coupon-modal');
    }

    public function save()
    {
        if($this->couponId)
        {
            $this->form->update($this->couponId);
        }
        else
        {
            $this->form->store();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'¡Cupón almacenado exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-coupon-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-couponsTable');

        //clear form
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->form->reset();
        $this->couponId = null;
    }

    public function clearService()
    {
        $this->form->service = null;
    }
}