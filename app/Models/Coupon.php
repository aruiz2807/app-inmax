<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int|null $service_id
 * @property string $name
 * @property string $type
 * @property numeric $value
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CouponDoctor> $couponDoctors
 * @property-read int|null $coupon_doctors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Doctor> $doctors
 * @property-read int|null $doctors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Service> $services
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
        'inmax_coverage_percent',
    ];

    /**
     * Each coupon may have one or many coupon doctors.
     */
    public function couponDoctors(): HasMany
    {
        return $this->hasMany(CouponDoctor::class);
    }

    /**
     * Each coupon may have one or many doctors
     */
    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'coupon_doctors');
    }

    /**
     * Servicio directo del cupón (cupones de tipo Service, o con un solo servicio).
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Servicios del cupón vía tabla pivote (cupones con varios servicios).
     * Verifica que el nombre de la tabla coincida con tu migración (coupon_service vs coupon_services).
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'coupon_service');
    }

    /**
     * IDs de servicios que cubre el cupón: el service_id directo o, si no tiene, los del pivote.
     * Es la única fuente de verdad para saber a qué servicios aplica un cupón.
     * Conviene cargar la relación `services` con eager loading para evitar N+1.
     *
     * @return array<int, int>
     */
    public function serviceIds(): array
    {
        $ids = $this->service_id
            ? [(int) $this->service_id]
            : $this->services->pluck('id')->map(fn ($id) => (int) $id)->all();

        return array_values(array_unique($ids));
    }

    /**
     * Each coupon can have many plan benefits.
     */
    public function planBenefits(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PlanBenefit::class);
    }
}