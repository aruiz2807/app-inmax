<?php

namespace App\Livewire\Doctors;

use App\Models\Coupon;
use App\Models\DoctorCoupon;
use Livewire\Component;
use Livewire\Attributes\On;

class DoctorCouponsModal extends Component
{
    public ?int $doctorId = null;
    public ?int $couponId = null;

    public $coupons = [];
    public $doctorCoupons = [];

    public function render()
    {
        return view('livewire.doctors.doctor-coupons-modal');
    }

    public function mount()
    {
        $this->coupons = Coupon::query()->where('status', 'Active')->get();
    }

    #[On('editCoupons')]
    public function editCoupons($doctorId)
    {
        $this->doctorId = $doctorId;
        $this->couponId = null;

        $this->loadDoctorCoupons();

        $this->dispatch('open-doctor-coupons-modal');
    }

    public function addCoupon()
    {
        if (!$this->couponId) 
        {
            return;
        }

        DoctorCoupon::create([
            'doctor_id' => $this->doctorId,
            'coupon_id' => $this->couponId,
        ]);

        $this->loadDoctorCoupons();
    }

    public function updateCoupons()
    {
        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Cupones otorgados almacenados exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-doctor-coupons-modal');
    }

    public function delete($doctorCouponId)
    {
        DoctorCoupon::whereKey($doctorCouponId)->delete();

        $this->loadDoctorCoupons();
    }

    private function loadDoctorCoupons()
    {
        $this->doctorCoupons = DoctorCoupon::with('coupon:id,name,type')
            ->where('doctor_id', $this->doctorId)
            ->get();

        $this->coupons = Coupon::query()
            ->where('status', 'Active')
            ->whereDoesntHave('doctorCoupons', fn ($query) =>
                $query->where('doctor_id', $this->doctorId)
            )
            ->get();
    }
}