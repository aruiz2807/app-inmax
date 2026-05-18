<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $appointment_id
 * @property string|null $symptoms
 * @property string|null $findings
 * @property string|null $diagnosis
 * @property string|null $treatment
 * @property string|null $notes
 * @property string|null $attachment_path
 * @property string|null $attachment_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Appointment $appointment
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote whereAppointmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote whereAttachmentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote whereAttachmentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote whereDiagnosis($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote whereFindings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote whereSymptoms($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote whereTreatment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentNote whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AppointmentNote extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'appointment_id',
        'symptoms',
        'findings',
        'diagnosis',
        'treatment',
        'notes',
        'attachment_path',
        'attachment_name',
    ];

    /**
     * Each note belongs to one appointment.
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
