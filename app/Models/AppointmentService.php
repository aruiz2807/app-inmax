<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $appointment_id
 * @property int|null $service_id
 * @property int|null $coupon_id
 * @property string|null $unregistered_service
 * @property int $covered
 * @property string|null $attachment_path
 * @property string|null $attachment_name
 * @property string $status
 * @property numeric|null $subtotal
 * @property numeric|null $coupon_discount
 * @property numeric|null $user_payment
 * @property numeric|null $commission
 * @property numeric|null $total
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
        'coupon_id',
        'unregistered_service',
        'covered',
        'status',
        'subtotal',
        'coupon_discount',
        'user_payment',
        'commission',
        'total',
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

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the service covered status icon
     */
    protected function getCoveredIconAttribute()
    {
        $icon = '';

        switch ($this->isEffectivelyCovered()) {
            case 'by service':
                $icon = 'shield-check';
                break;
            case 'by coupon':
                $icon = 'ticket';
                break;
            case false:
                $icon = 'shield-exclamation';
                break;
        }

        return $icon;
    }

    /**
     * Get the service covered status color
     */
    protected function getCoveredColorAttribute()
    {
        $color = '';

        switch ($this->isEffectivelyCovered()) {
            case 'by service':
                $color = 'green';
                break;
            case 'by coupon':
                $color = 'blue';
                break;
            case false:
                $color = 'yellow';
                break;
        }

        return $color;
    }

    /**
     * Get the service covered text
     */
    protected function getCoveredTextAttribute()
    {
        $text = '';

        switch ($this->isEffectivelyCovered()) {
            case 'by service':
                $text = 'Incluido';
                break;
            case 'by coupon':
                $text = 'Cupón';
                break;
            case false:
                $text = 'Adicional';
                break;
        }

        return $text;
    }

    /**
     * Whether the service is covered, either stored as such or via an unused "Service" coupon on the patient's policy.
     * 
     * For unpaid appointments: check if service has coupon_id
     * For paid appointments: check if coupon_discount > 0
     */
    public function isEffectivelyCovered()
    {
        $appointment = $this->appointment;
        $isPaid = $appointment && $appointment->status === AppointmentStatus::COMPLETED;
    
        if ($this->coupon_id) {
            if ($isPaid) {
                // Coupon was associated, but check if it was actually applied
                return $this->coupon_discount > 0 ? 'by coupon' : false;
            }
            // Unpaid appointment with coupon associated
            return 'by coupon';
        } else {
            return false;
        }

    }

    /**
     * Get the service name (either from relationship or unregistered field)
     */
    protected function getNameAttribute()
    {
        return $this->service?->name ?? $this->unregistered_service;
    }
}
