<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $specialty_id
 * @property int $service_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Service $service
 * @property-read \App\Models\Specialty $specialty
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpecialtyService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpecialtyService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpecialtyService query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpecialtyService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpecialtyService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpecialtyService whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpecialtyService whereSpecialtyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SpecialtyService whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class SpecialtyService extends Model
{
    protected $table = 'specialty_services';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'specialty_id',
        'service_id',
    ];

    /**
     * Each service belongs to one specialty.
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /**
     * Each service can have or be one kind of service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
