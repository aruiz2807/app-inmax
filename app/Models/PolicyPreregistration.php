<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PolicyPreregistration extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sales_user_id',
        'plan_id',
        'parent_policy_id',
        'phone',
        'token_hash',
        'expires_at',
        'used_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    /**
     * Scope active preregistrations.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('used_at')
            ->where('expires_at', '>', now());
    }

    /**
     * The sales agent who created the preregistration.
     */
    public function salesUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_user_id');
    }

    /**
     * The plan reserved for this preregistration.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * The optional parent policy selected during preregistration.
     */
    public function parentPolicy(): BelongsTo
    {
        return $this->belongsTo(Policy::class, 'parent_policy_id');
    }

    /**
     * The policy created from this preregistration.
     */
    public function policy(): HasOne
    {
        return $this->hasOne(Policy::class, 'policy_preregistration_id');
    }
}
