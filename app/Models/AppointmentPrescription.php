<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $appointment_id
 * @property int $medication_id
 * @property int $quantity
 * @property string $dose
 * @property string $frequency
 * @property string $duration
 * @property int|null $delivered_quantity
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Appointment $appointment
 * @property-read \App\Models\Medication $medication
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription whereAppointmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription whereDeliveredQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription whereDose($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription whereFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription whereMedicationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentPrescription whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AppointmentPrescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'medication_id',
        'description',
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
