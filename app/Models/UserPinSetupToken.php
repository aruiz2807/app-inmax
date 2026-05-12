<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $created_by
 * @property string $token_hash
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\UserLegalAcceptance|null $legalAcceptance
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPinSetupToken newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPinSetupToken newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPinSetupToken query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPinSetupToken whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPinSetupToken whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPinSetupToken whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPinSetupToken whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPinSetupToken whereTokenHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPinSetupToken whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPinSetupToken whereUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPinSetupToken whereUserId($value)
 * @mixin \Eloquent
 */
class UserPinSetupToken extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'created_by',
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
     * The user that will setup the pin.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin that created this setup token.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Legal acceptance generated through this token.
     */
    public function legalAcceptance(): HasOne
    {
        return $this->hasOne(UserLegalAcceptance::class, 'user_pin_setup_token_id');
    }
}
