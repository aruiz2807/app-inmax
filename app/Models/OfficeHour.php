<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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