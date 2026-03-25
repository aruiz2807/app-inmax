<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentService extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'appointment_id',
        'service_id',
        'covered',
        'status',
        'attachment_path',
        'attachment_name',
    ];

    /**
     * Each service belongs to one appointment.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Each service belongs to one service.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the service covered status icon
     */
    protected function getCoveredIconAttribute()
    {
        return $this->covered ? 'shield-check' : 'shield-exclamation';
    }

    /**
     * Get the service covered status color
     */
    protected function getCoveredColorAttribute()
    {
        return $this->covered ? 'green' : 'yellow';
    }

    /**
     * Get the service covered text
     */
    protected function getCoveredTextAttribute()
    {
        return $this->covered ? 'Incluido' : 'Adicional';
    }
}
