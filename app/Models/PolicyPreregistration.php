<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PolicyPreregistration extends Model
{
    public const TYPE_INDIVIDUAL_POLICY = 'individual_policy';

    public const TYPE_GROUP_OWNER = 'group_owner';

    public const TYPE_GROUP_MEMBER = 'group_member';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sales_user_id',
        'plan_id',
        'parent_policy_id',
        'preregistration_type',
        'company_name',
        'company_type',
        'company_legal_name',
        'company_rfc',
        'members',
        'phone',
        'token_hash',
        'expires_at',
        'used_at',
        'cancelled_by',
        'cancelled_at',
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
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Scope active preregistrations.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('used_at')
            ->whereNull('cancelled_at')
            ->where('expires_at', '>', now());
    }

    /**
     * Human readable status label for the preregistration.
     */
    public function getStatusLabelAttribute(): string
    {
        return match (true) {
            $this->cancelled_at !== null => 'Cancelado',
            $this->used_at !== null => 'Registrado',
            $this->expires_at->isPast() => 'Expirado',
            default => 'Pendiente',
        };
    }

    /**
     * Badge color for UI rendering.
     */
    public function getStatusColorAttribute(): string
    {
        return match (true) {
            $this->cancelled_at !== null => 'rose',
            $this->used_at !== null => 'emerald',
            $this->expires_at->isPast() => 'amber',
            default => 'teal',
        };
    }

    /**
     * Determine if the preregistration can still be edited or cancelled.
     */
    public function canBeManaged(): bool
    {
        return $this->used_at === null && $this->cancelled_at === null;
    }

    /**
     * Human readable preregistration type.
     */
    public function getTypeLabelAttribute(): string
    {
        return match (true) {
            $this->isGroupOwner() => 'Titular colectiva',
            $this->isGroupMember() => 'Miembro colectiva',
            default => 'Membresía individual',
        };
    }

    /**
     * Determine whether the preregistration is for a collective owner policy.
     */
    public function isGroupOwner(): bool
    {
        return $this->preregistration_type === self::TYPE_GROUP_OWNER;
    }

    /**
     * Determine whether the preregistration reserves a collective member slot.
     */
    public function isGroupMember(): bool
    {
        return $this->preregistration_type === self::TYPE_GROUP_MEMBER;
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

    /**
     * The admin or sales user that cancelled the preregistration.
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
