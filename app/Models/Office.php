<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'maps_url',
        'phone_number',
    ];

    /**
     * Each office belongs to one or many doctors.
     */
    public function doctors()
    {
        return $this->belongsToMany(Doctor::class, 'office_doctors');
    }
    
    /**
     * Each office can have multiple hours.
     */
    public function officeHours()
    {
        return $this->hasMany(OfficeHour::class);
    }
}
