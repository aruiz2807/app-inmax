<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $doctor_id
 * @property int $coupon_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Coupon $coupon
 * @property-read \App\Models\Doctor $doctor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlanBenefit> $planBenefits
 * @property-read int|null $plan_benefits_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponDoctor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponDoctor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponDoctor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponDoctor whereCouponId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponDoctor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponDoctor whereDoctorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponDoctor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CouponDoctor whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class CouponDoctor extends Model
{
    protected $table = 'coupon_doctors';

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
     * Each coupon belongs to one or many doctor.
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