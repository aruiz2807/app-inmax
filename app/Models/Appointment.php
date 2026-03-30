<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Appointment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'doctor_id',
        'office_id',
        'requested_by_user_id',
        'date',
        'time',
        'rating',
        'comments',
        'status',
        'subtotal',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i:s',
    ];

    /**
     * Get the appointment icon according status
     */
    protected function getStatusIconAttribute()
    {
        $icon = '';

        if($this->status === 'Cancelled')
        {
            $icon = "x-circle";
        }
        else if($this->status === 'No-show')
        {
            $icon = "eye-slash";
        }
        else
        {
            $icon = "shield-check";
        }

        return $icon;
    }

    /**
     * Get the appointment color according status
     */
    protected function getStatusColorAttribute()
    {
        $color = '';

        if($this->status === 'Cancelled')
        {
            $color = "red";
        }
        else if($this->status === 'No-show')
        {
            $color = "red";
        }
        else
        {
            $color = "teal";
        }

        return $color;
    }

    /**
     * Get the appointment status formatted
     */
    protected function getFormattedStatusAttribute()
    {
        $status = '';

        if($this->status === 'Cancelled')
        {
            $status = "Cancelada";
        }
        else if($this->status === 'No-show')
        {
            $status = "No se presento";
        }
        else
        {
            $status = "Atendida";
        }

        return $status;
    }

    /**
     * Get the appointment covered status color
     */
    protected function getCoveredColorAttribute()
    {
        return $this->covered ? 'green' : 'yellow';
    }

    /**
     * Get the appointment covered text
     */
    protected function getCoveredTextAttribute()
    {
        return $this->covered ? 'Cubierta' : 'Adicional';
    }

    /**
     * Get the appointment covered status icon
     */
    protected function getCoveredIconAttribute()
    {
        return $this->covered ? 'shield-check' : 'shield-exclamation';
    }

    /*
    *
    */
    public function getFormattedDateAttribute()
    {
        return ucfirst($this->date
            ->locale('es_MX')
            ->translatedFormat('l d \de F \de Y'));
    }

    /*
    *
    */
    public function getFormattedTimeAttribute()
    {
        return $this->time->format('H:i A');
    }

    /**
     * Each appointment belongs to one user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Each appointment could be assigned to one doctor.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Each appointment could be requested by one user.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    /**
     * Each appointment is assigned to one office.
     */
    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    /**
    * An appointment can only have one Note.
    */
    public function note(): HasOne
    {
        return $this->hasOne(AppointmentNote::class);
    }

    /**
    * An appointment can have many services.
    */
    public function services(): HasMany
    {
        return $this->hasMany(AppointmentService::class);
    }
}
