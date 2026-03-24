<?php

namespace App\Models;

use App\Enums\DoctorType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'office_id',
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
     * Each doctor can have one office
     */
    public function office()
    {
        return $this->belongsTo(Office::class);
    }
}
