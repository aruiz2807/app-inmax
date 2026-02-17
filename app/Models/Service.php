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
}
