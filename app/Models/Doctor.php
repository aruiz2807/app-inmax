<?php

namespace App\Models;

use App\Enums\DoctorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int $specialty_id
 * @property DoctorType $type
 * @property int|null $discount
 * @property int|null $commission
 * @property string $license
 * @property string $university
 * @property string $address
 * @property string|null $maps_url
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DoctorCoupon> $doctorCoupons
 * @property-read int|null $doctor_coupons_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DoctorService> $doctorServices
 * @property-read int|null $doctor_services_count
 * @property-read mixed $formatted_status
 * @property-read mixed $rating
 * @property-read mixed $status_color
 * @property-read mixed $status_icon
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Office> $offices
 * @property-read int|null $offices_count
 * @property-read \App\Models\Specialty $specialty
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $staff
 * @property-read int|null $staff_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereLicense($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereMapsUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereSpecialtyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereUniversity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Doctor whereUserId($value)
 * @mixin \Eloquent
 */
class Doctor extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'specialty_id',
        'type',
        'license',
        'university',
        'address',
        'maps_url',
        'discount',
        'commission',
    ];

    /**
     * The attributes that are appended when model is retrieved
     *
     * @var array<int, string>
     */
    protected $appends = [
        'rating',
    ];

    /**
     * The attributes that are being casted
     *
     * @var array<int, string>
     */
    protected $casts = [
        'type' => DoctorType::class,
    ];

    /**
     * Get the appointment icon according status
     */
    protected function getFormattedStatusAttribute()
    {
        return $this->status === 'Active' ? 'Activo' : 'Inactivo';
    }

    /**
     * Get the doctor icon according to status
     */
    protected function getStatusIconAttribute()
    {
        return $this->status === 'Active' ? 'shield-check' : 'shield-exclamation';
    }

    /**
     * Get the doctor color according to status
     */
    protected function getStatusColorAttribute()
    {
        return $this->status === 'Active' ? 'green' : 'gray';
    }

    /**
     * Get the doctor's rating
     */
    protected function getRatingAttribute()
    {
        $rating = rand(1, 5);

        return $rating;
    }

    /**
     * Each doctor can have one user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Each doctor can have one specialty.
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }

    /**
     * Each doctor can have many doctor services.
     */
    public function doctorServices(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DoctorService::class);
    }

    /**
     * Each doctor can have many doctor coupons.
     */
    public function doctorCoupons(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(DoctorCoupon::class);
    }

    /**
     * Each doctor can have multiple offices
     */
    public function offices()
    {
        return $this->belongsToMany(Office::class, 'office_doctors');
    }

    /**
     * Staff members (Clerk / Receptionist) assigned to this doctor.
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'doctor_staff');
    }
}
