<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorCoupon extends Model
{
    protected $table = 'doctor_coupons';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'doctor_id',
        'coupon_id',
    ];

    /**
     * Each coupon belongs to one doctor.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Each coupon can have or be one kind of coupon.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Each coupon can have many plan benefits.
     */
    public function planBenefits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlanBenefit::class);
    }
}