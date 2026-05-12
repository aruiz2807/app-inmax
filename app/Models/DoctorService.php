<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $doctor_id
 * @property int $service_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Doctor $doctor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlanBenefit> $planBenefits
 * @property-read int|null $plan_benefits_count
 * @property-read \App\Models\Service $service
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DoctorService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DoctorService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DoctorService query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DoctorService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DoctorService whereDoctorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DoctorService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DoctorService whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DoctorService whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
