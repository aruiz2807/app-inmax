<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorService extends Model
{
    protected $table = 'doctor_services';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'doctor_id',
        'service_id',
    ];

    /**
     * Each service belongs to one doctor.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Each service can have or be one kind of service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Each service can have many plan benefits.
     */
    public function planBenefits(): HasMany
    {
        return $this->hasMany(PlanBenefit::class);
    }
}
