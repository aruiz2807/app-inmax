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
    ];

    /**
     * Each office belongs to one or many doctors.
     */
    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }
}
