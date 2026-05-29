<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $service_id
 * @property string $name
 * @property string $type
 * @property numeric $value
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DoctorCoupon> $doctorCoupons
 * @property-read int|null $doctor_coupons_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Doctor> $doctors
 * @property-read int|null $doctors_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Coupon whereValue($value)
 * @mixin \Eloquent
 */
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
        'limit_min',
        'limit_max',
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

    /**
     * Each coupon can have or be one kind of service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Each coupon can have many plan benefits.
     */
    public function planBenefits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlanBenefit::class);
    }
}
