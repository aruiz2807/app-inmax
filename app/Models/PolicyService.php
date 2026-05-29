<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $policy_id
 * @property int|null $service_id
 * @property int|null $doctor_service_id
 * @property int|null $doctor_coupon_id
 * @property int $included
 * @property int $used
 * @property int $extra
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\DoctorCoupon|null $doctorCoupon
 * @property-read \App\Models\DoctorService|null $doctorService
 * @property-read mixed $color
 * @property-read mixed $level
 * @property-read \App\Models\Policy $policy
 * @property-read \App\Models\Service|null $service
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyService query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyService whereDoctorCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyService whereDoctorServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyService whereExtra($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyService whereIncluded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyService wherePolicyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyService whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyService whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PolicyService whereUsed($value)
 * @mixin \Eloquent
 */
class PolicyService extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'policy_id',
        'service_id',
        'coupon_id',
        'doctor_service_id',
        'doctor_coupon_id',
        'included',
        'used',
        'extra',
    ];

    /**
     * Get the service's level
     */
    protected function getLevelAttribute()
    {
        $level = '';

        if($this->used == 0)
        {
            $level = "shield-check";
        }
        else if($this->used >= $this->included)
        {
            $level = "shield-exclamation";
        }
        else
        {
            $level = "shield-check";
        }

        return $level;
    }

    /**
     * Get the service's color
     */
    protected function getColorAttribute()
    {
        $level = '';

        if($this->used == 0)
        {
            $level = "fill-lime-400";
        }
        else if($this->used >= $this->included)
        {
            $level = "fill-red-400";
        }
        else
        {
            $level = "fill-amber-400";
        }

        return $level;
    }

    /**
     * Each policy service belongs to one policy.
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    /**
     * Each policy service can belong to one type of general service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Each policy service can belong to a coupon.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Each policy service can belong to a doctor service.
     */
    public function doctorService(): BelongsTo
    {
        return $this->belongsTo(DoctorService::class);
    }

    /**
     * Each policy service can belong to a doctor coupon.
     */
    public function doctorCoupon(): BelongsTo
    {
        return $this->belongsTo(DoctorCoupon::class);
    }
}
