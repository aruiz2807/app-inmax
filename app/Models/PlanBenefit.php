<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $plan_id
 * @property int|null $doctor_service_id
 * @property int|null $doctor_coupon_id
 * @property int|null $events
 * @property numeric|null $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\DoctorCoupon|null $doctorCoupon
 * @property-read \App\Models\DoctorService|null $doctorService
 * @property-read \App\Models\Plan $plan
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanBenefit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanBenefit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanBenefit query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanBenefit whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanBenefit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanBenefit whereDoctorCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanBenefit whereDoctorServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanBenefit whereEvents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanBenefit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanBenefit wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlanBenefit whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
        'coupon_id',
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

    /**
     * Each benefit can belong to a coupon.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
