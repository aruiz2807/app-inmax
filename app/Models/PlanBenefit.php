<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanBenefit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plan_id',
        'doctor_service_id',
        'doctor_coupon_id',
        'events',
        'amount',
    ];

    /**
     * Each benefit belongs to one plan.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Each benefit can belong to a doctor service.
     */
    public function doctorService(): BelongsTo
    {
        return $this->belongsTo(DoctorService::class);
    }

    /**
     * Each benefit can belong to a doctor coupon.
     */
    public function doctorCoupon(): BelongsTo
    {
        return $this->belongsTo(DoctorCoupon::class);
    }
}
