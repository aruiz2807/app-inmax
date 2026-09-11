<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppMarketingCampaignRecipient extends Model
{
    protected $table = 'whatsapp_marketing_campaign_recipients';

    public const STATUS_PENDING = 'pending';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'whatsapp_marketing_campaign_id',
        'row_number',
        'customer_name',
        'phone_raw',
        'phone_normalized',
        'row_data',
        'body_values',
        'button_values',
        'status',
        'wamid',
        'error_message',
        'queued_at',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'response_type',
        'response_text',
        'response_payload',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'row_data' => 'array',
            'body_values' => 'array',
            'button_values' => 'array',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
            'response_payload' => 'array',
            'responded_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMarketingCampaign::class, 'whatsapp_marketing_campaign_id');
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(WhatsAppMarketingCampaignDeliveryLog::class, 'whatsapp_marketing_campaign_recipient_id');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_QUEUED => 'En cola',
            self::STATUS_SENT => 'Enviado',
            self::STATUS_DELIVERED => 'Entregado',
            self::STATUS_READ => 'Leido',
            self::STATUS_FAILED => 'Fallido',
            default => ucfirst((string) $this->status),
        };
    }

    public function responseTypeLabel(): string
    {
        return match ($this->response_type) {
            'button' => 'Boton',
            'interactive' => 'Boton interactivo',
            'text' => 'Mensaje directo',
            'media' => 'Multimedia',
            default => $this->response_type ? ucfirst((string) $this->response_type) : 'Sin respuesta',
        };
    }
}
