<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string $type
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlanCoverage> $coverage
 * @property-read int|null $coverage_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DoctorService> $doctorServices
 * @property-read int|null $doctor_services_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Doctor> $doctors
 * @property-read int|null $doctors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlanBenefit> $planBenefits
 * @property-read int|null $plan_benefits_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Specialty> $specialties
 * @property-read int|null $specialties_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SpecialtyService> $specialtyServices
 * @property-read int|null $specialty_services_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Service whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Service extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
    ];

    /**
     * Each service can have many doctor services.
     */
    public function doctorServices(): HasMany
    {
        return $this->hasMany(DoctorService::class);
    }

    /**
     * Each service can have many plan benefits through doctor services.
     */
    public function planBenefits(): HasManyThrough
    {
        return $this->hasManyThrough(PlanBenefit::class, DoctorService::class);
    }

    /**
     * Each service may have one or many plan coverage.
     */
    public function coverage(): HasMany
    {
        return $this->hasMany(PlanCoverage::class);
    }

    /**
     * Each service may have one or many specialty services.
     */
    public function specialtyServices(): HasMany
    {
        return $this->hasMany(SpecialtyService::class);
    }

    /**
     * Each service may have one or many specialties
     */
    public function specialties()
    {
        return $this->belongsToMany(Specialty::class, 'specialty_services');
    }

    /**
     * Each service may have one or many doctors
     */
    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'doctor_services');
    }
}
