<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Policy extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'plan_id',
        'parent_policy_id',
        'number',
        'start_date',
        'end_date',
        'insurance',
    ];

    /**
     * The attributes that are casted
     *
     * @var array<int, string>
     */
    protected $casts = [
        'insurance' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Each policy belongs to only one user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Each policy can have one insurance plan.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // The policy could have a self-referencing parent policy
    public function parentPolicy()
    {
        return $this->belongsTo(Policy::class, 'parent_policy_id');
    }

    // The policy could have many self-referencing child policies
    public function childPolicies()
    {
        return $this->hasMany(Policy::class, 'parent_policy_id');
    }
}
