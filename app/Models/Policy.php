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
        'sales_user_id',
        'plan_id',
        'parent_policy_id',
        'policy_preregistration_id',
        'number',
        'type',
        'members',
        'start_date',
        'end_date',
        'insurance',
        'payment_method',
        'payment_reference',
        'payment_file_path',
        'payment_file_name',
        'status',
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
     * Get the appointment covered status color
     */
    protected function getStatusColorAttribute()
    {
        return $this->status === 'Active' ? 'green' : 'gray';
    }

    /**
     * Get the appointment covered text
     */
    protected function getStatusTextAttribute()
    {
        return $this->status === 'Active' ? 'Cobertura activa' : 'Cobertura inactiva';
    }

    /**
     * Get the appointment covered status icon
     */
    protected function getStatusIconAttribute()
    {
        return $this->status === 'Active' ? 'shield-check' : 'shield-exclamation';
    }

    /**
     * Each policy belongs to only one user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Each policy can have only one sales agent.
     */
    public function sales_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_user_id');
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

    /**
     * The preregistration that originated this policy.
     */
    public function preregistration(): BelongsTo
    {
        return $this->belongsTo(PolicyPreregistration::class, 'policy_preregistration_id');
    }

    // The policy could have many self-referencing child policies
    public function childPolicies()
    {
        return $this->hasMany(Policy::class, 'parent_policy_id');
    }
}
