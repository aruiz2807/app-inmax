<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanBenefit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'plan_id',
        'service_id',
        'events',
        'amount',
    ];

    /**
     * Each benefit belongs to one plan.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Each benefit can have or be one kind of service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
