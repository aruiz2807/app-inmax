<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $office_id
 * @property string $slot
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Office $office
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeHour newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeHour newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeHour query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeHour whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeHour whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeHour whereOfficeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeHour whereSlot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OfficeHour whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class OfficeHour extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'office_id',
        'slot',
    ];

    /**
     * Each office hour belongs to an office.
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }
}