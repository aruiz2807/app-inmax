<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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
