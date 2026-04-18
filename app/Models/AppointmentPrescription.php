<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentPrescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'medication_id',
        'quantity',
        'dose',
        'frequency',
        'duration',
        'delivered_quantity',
        'status',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function medication(): BelongsTo
    {
        return $this->belongsTo(Medication::class);
    }
}
