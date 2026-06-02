<?php

namespace App\Livewire\Coupons;

use App\Models\Doctor;
use App\Models\CouponDoctor;
use Livewire\Component;
use Livewire\Attributes\On;

class CouponDoctorsModal extends Component
{
    public ?int $doctorId = null;
    public ?int $couponId = null;

    public $doctors = [];
    public $couponDoctors = [];

    public function render()
    {
        return view('livewire.coupons.coupon-doctors-modal');
    }

    public function mount()
    {
        $this->doctors = Doctor::query()->where('status', 'Active')->get();
    }

    #[On('editDoctors')]
    public function editDoctors($couponId)
    {
        $this->couponId = $couponId;
        $this->doctorId = null;

        $this->loadCouponDoctors();

        $this->dispatch('open-coupon-doctors-modal');
    }

    public function addDoctor()
    {
        if (!$this->doctorId) 
        {
            return;
        }

        CouponDoctor::create([
            'coupon_id' => $this->couponId,
            'doctor_id' => $this->doctorId,
        ]);

        $this->loadCouponDoctors();
    }

    public function updateDoctors()
    {
        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'¡Proveedores asignados almacenados exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-coupon-doctors-modal');
    }

    public function delete($couponDoctorId)
    {
        CouponDoctor::whereKey($couponDoctorId)->delete();

        $this->loadCouponDoctors();
    }

    private function loadCouponDoctors()
    {
        $this->couponDoctors = CouponDoctor::with('doctor:id,type,user_id','doctor.user:id,name')
            ->where('coupon_id', $this->couponId)
            ->get();

        $this->doctors = Doctor::query()
            ->where('status', 'Active')
            ->whereDoesntHave('couponDoctors', fn ($query) =>
                $query->where('coupon_id', $this->couponId)
            )
            ->get();
    }
}