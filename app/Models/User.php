<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $name
 * @property string $profile
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $phone
 * @property string $phone_country_code
 * @property Carbon|null $phone_verified_at
 * @property Carbon|null $birth_date
 * @property string|null $curp
 * @property string|null $passport
 * @property int|null $company_id
 * @property string $password
 * @property string|null $pin
 * @property Carbon|null $pin_set_at
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property int|null $current_team_id
 * @property string|null $profile_photo_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\Company|null $company
 * @property-read \App\Models\Doctor|null $doctor
 * @property-read mixed $age
 * @property-read mixed $photo_url
 * @property-read string $clean_phone
 * @property-read bool $is_dependent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserLegalAcceptance> $legalAcceptances
 * @property-read int|null $legal_acceptances_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \App\Models\Policy|null $policy
 * @property-read string $profile_photo_url
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Doctor> $staffDoctors
 * @property-read int|null $staff_doctors_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCompanyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCurrentTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoneCountryCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePhoneVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePinSetAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfile($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereProfilePhotoPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'pin',
        'pin_set_at',
        'profile',
        'phone',
        'phone_country_code',
        'phone_verified_at',
        'birth_date',
        'company_id',
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'pin',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'pin' => 'hashed',
            'pin_set_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'birth_date' => 'date',
        ];
    }

    /**
     * Get the user age.
     */
    public function getAgeAttribute()
    {
        try {
            return Carbon::parse($this->birth_date)->age;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * User profile photo url.
     */
    public function getPhotoUrlAttribute()
    {
        if($this->profile_photo_path)
        {
            return Storage::disk('public')->url($this->profile_photo_path);
        }
        else if ($this->profile === 'User')
        {
            return '/img/user.png';
        }
        else if ($this->profile === 'Doctor')
        {
            return '/img/doctor.png';
        }
    }

    /**
     * Get the clean 10-digit base phone number without suffix.
     */
    public function getCleanPhoneAttribute(): string
    {
        return explode('-', $this->phone)[0];
    }

    /**
     * Check if this user is a dependent.
     */
    public function getIsDependentAttribute(): bool
    {
        $parts = explode('-', $this->phone);
        return isset($parts[1]) && $parts[1] !== '01' && $parts[1] !== '00';
    }

    /**
     * Mutator to keep the suffix of the phone field intact when updated from a 10-digit input.
     */
    public function setPhoneAttribute($value): void
    {
        if (str_contains((string) $value, '-')) {
            $this->attributes['phone'] = $value;
            return;
        }

        $currentSuffix = null;
        if (isset($this->attributes['phone'])) {
            $parts = explode('-', $this->attributes['phone']);
            if (isset($parts[1])) {
                $currentSuffix = $parts[1];
            }
        }

        if ($currentSuffix !== null) {
            $this->attributes['phone'] = $value . '-' . $currentSuffix;
        } else {
            $this->attributes['phone'] = $value;
        }
    }

    /**
     * A user can only belong to one Doctor.
     */
    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    /**
     * A user can only be part of one company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * A user can only have one policy.
     */
    public function policy(): HasOne
    {
        return $this->hasOne(Policy::class);
    }

    /**
     * Legal acceptance records created by this user.
     */
    public function legalAcceptances(): HasMany
    {
        return $this->hasMany(UserLegalAcceptance::class);
    }

    /**
     * Permissions assigned directly to this user.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    /**
     * Determine whether the user has the given active permission.
     */
    public function hasPermission(string $code): bool
    {
        if ($code === '') {
            return false;
        }

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains(
                fn (Permission $permission): bool => $permission->code === $code && $permission->is_active
            );
        }

        return $this->permissions()
            ->where('code', $code)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Determine whether the user has at least one active permission from the given set.
     *
     * @param  iterable<int, string>  $codes
     */
    public function hasAnyPermission(iterable $codes): bool
    {
        $codes = collect($codes)
            ->filter(fn (mixed $code): bool => is_string($code) && $code !== '')
            ->values()
            ->all();

        if ($codes === []) {
            return false;
        }

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains(
                fn (Permission $permission): bool => $permission->is_active && in_array($permission->code, $codes, true)
            );
        }

        return $this->permissions()
            ->whereIn('code', $codes)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Doctors associated to this staff member (Clerk / Receptionist).
     */
    public function staffDoctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'doctor_staff');
    }
}
