<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyService extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'policy_id',
        'service_id',
        'included',
        'used',
        'extra',
    ];

    /**
     * Get the service's level
     */
    protected function getLevelAttribute()
    {
        $level = '';

        if($this->used == 0)
        {
            $level = "shield-check";
        }
        else if($this->used >= $this->included)
        {
            $level = "shield-exclamation";
        }
        else
        {
            $level = "shield-check";
        }

        return $level;
    }

    /**
     * Get the service's color
     */
    protected function getColorAttribute()
    {
        $level = '';

        if($this->used == 0)
        {
            $level = "fill-lime-400";
        }
        else if($this->used >= $this->included)
        {
            $level = "fill-red-400";
        }
        else
        {
            $level = "fill-amber-400";
        }

        return $level;
    }

    /**
     * Each policy service belongs to one policy.
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }

    /**
     * Each policy service belongs to one type of service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
