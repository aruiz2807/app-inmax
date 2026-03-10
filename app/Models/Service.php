<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Each service may have one or many plan benefits.
     */
    public function benefits(): HasMany
    {
        return $this->hasMany(PlanBenefit::class);
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
}
