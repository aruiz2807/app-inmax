<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $sales_user_id
 * @property int $plan_id
 * @property int|null $parent_policy_id
 * @property int|null $policy_preregistration_id
 * @property string $number
 * @property string $type
 * @property int|null $members
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property array<array-key, mixed>|null $insurance
 * @property string|null $payment_method
 * @property string|null $payment_reference
 * @property string|null $payment_file_path
 * @property string|null $payment_file_name
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Policy> $childPolicies
 * @property-read int|null $child_policies_count
 * @property-read mixed $status_color
 * @property-read mixed $status_icon
 * @property-read mixed $status_text
 * @property-read Policy|null $parentPolicy
 * @property-read \App\Models\Plan $plan
 * @property-read \App\Models\PolicyPreregistration|null $preregistration
 * @property-read \App\Models\User|null $sales_user
 * @property-read \App\Models\User $user
 * @property-read \App\Models\PolicyLegalInformation|null $policyLegalInformation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereInsurance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereMembers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereParentPolicyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy wherePaymentFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy wherePaymentFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy wherePaymentReference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy wherePlanId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy wherePolicyPreregistrationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereSalesUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Policy whereUserId($value)
 * @mixin \Eloquent
 */
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
        return $this->status === 'Active' ? 'Membresía activa' : 'Membresía inactiva';
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

    /**
     * Get the legal information associated with the policy.
     */
    public function policyLegalInformation(): HasOne
    {
        return $this->hasOne(PolicyLegalInformation::class);
    }
}
