<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Specialty extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'service_id',
    ];

    /**
     * Each specialty may be assigned to one or many docotrs.
     */
    public function benefits(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    /**  !! UPDATE OR DELETE !!
     * Each specialty can be of one service type.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * many-to-many relationship between specialty and service
     */
    public function services()
    {
        return $this->belongsToMany(Service::class, 'specialty_services');
    }
}
