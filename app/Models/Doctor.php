<?php

namespace App\Models;

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
        'license',
        'university',
        'address',
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
}
