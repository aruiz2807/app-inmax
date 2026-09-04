<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $appointment_id
 * @property int|null $service_id
 * @property string|null $unregistered_service
 * @property int $covered
 * @property string|null $attachment_path
 * @property string|null $attachment_name
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Appointment $appointment
 * @property-read mixed $covered_color
 * @property-read mixed $covered_icon
 * @property-read mixed $covered_text
 * @property-read \App\Models\Service $service
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentService newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentService newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentService query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentService whereAppointmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentService whereAttachmentName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentService whereAttachmentPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentService whereCovered($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentService whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentService whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentService whereServiceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentService whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppointmentService whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
        'unregistered_service',
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
        return $this->isEffectivelyCovered() ? 'shield-check' : 'shield-exclamation';
    }

    /**
     * Get the service covered status color
     */
    protected function getCoveredColorAttribute()
    {
        return $this->isEffectivelyCovered() ? 'green' : 'yellow';
    }

    /**
     * Get the service covered text
     */
    protected function getCoveredTextAttribute()
    {
        return $this->isEffectivelyCovered() ? 'Incluido' : 'Adicional';
    }

    /**
     * Whether the service is covered, either stored as such or via an unused "Service" coupon on the patient's policy.
     */
    public function isEffectivelyCovered(): bool
    {
        if ($this->covered) {
            return true;
        }

        if (! $this->service_id) {
            return false;
        }

        $policy = $this->appointment?->user?->policy;

        if (! $policy) {
            return false;
        }

        $policyId = $policy->type === 'Member' ? $policy->parent_policy_id : $policy->id;

        return PolicyService::coversService($policyId, $this->service_id, $this->appointment->doctor_id);
    }

    /**
     * Get the service name (either from relationship or unregistered field)
     */
    protected function getNameAttribute()
    {
        return $this->service?->name ?? $this->unregistered_service;
    }
}
