<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'date',
        'time',
        'covered',
        'status',
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
     * Each appointment is assigned to one doctor.
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
