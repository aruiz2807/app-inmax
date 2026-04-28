<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'service_id',
        'name',
        'type',
        'value',
    ];

    /**
     * Each coupon may have one or many doctor coupons.
     */
    public function doctorCoupons(): HasMany
    {
        return $this->hasMany(DoctorCoupon::class);
    }

    /**
     * Each coupon may have one or many doctors
     */
    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'doctor_coupons');
    }
}
