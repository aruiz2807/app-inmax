<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $doctor_id
 * @property int|null $office_id
 * @property int|null $requested_by_user_id
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon $time
 * @property numeric|null $subtotal
 * @property numeric|null $coupon_discount
 * @property numeric|null $user_payment
 * @property numeric|null $commission
 * @property numeric|null $total
 * @property int|null $rating
 * @property string|null $comments
 * @property \App\Enums\AppointmentStatus|null $status
 * @property string|null $status_prescription
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Doctor|null $doctor
 * @property-read mixed $covered_color
 * @property-read mixed $covered_icon
 * @property-read mixed $covered_text
 * @property-read mixed $formatted_date
 * @property-read mixed $formatted_status
 * @property-read mixed $formatted_time
 * @property-read mixed $status_color
 * @property-read mixed $status_icon
 * @property-read \App\Models\AppointmentNote|null $note
 * @property-read \App\Models\Office|null $office
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AppointmentPrescription> $prescriptions
 * @property-read int|null $prescriptions_count
 * @property-read \App\Models\User|null $requester
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AppointmentService> $services
 * @property-read int|null $services_count
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereCouponDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereDoctorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereOfficeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereRequestedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereStatusPrescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Appointment whereUserPayment($value)
 * @mixin \Eloquent
 */
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
        'coupon_discount',
        'user_payment',
        'commission',
        'total',
        'payment_method',
        'payment_reference',
        'payment_attachment_path',
        'payment_attachment_name',
        'status_prescription'
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime:H:i:s',
        'status' => \App\Enums\AppointmentStatus::class,
    ];

    /**
     * Get the appointment icon according status
     */
    protected function getStatusIconAttribute()
    {
        return $this->status?->icon() ?? 'information-circle';
    }

    /**
     * Get the appointment color according status
     */
    protected function getStatusColorAttribute()
    {
        return $this->status?->color() ?? 'gray';
    }

    /**
     * Get the appointment status formatted
     */
    protected function getFormattedStatusAttribute()
    {
        return $this->status?->label() ?? '';
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

    /**
    * An appointment can have many prescriptions.
    */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(AppointmentPrescription::class);
    }
}
