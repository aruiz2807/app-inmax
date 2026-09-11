<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class WhatsAppMarketingCampaign extends Model
{
    protected $table = 'whatsapp_marketing_campaigns';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENDING = 'sending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'whatsapp_console_template_id',
        'created_by_user_id',
        'name',
        'description',
        'status',
        'body_mappings',
        'button_mappings',
        'header_media_type',
        'header_media_source',
        'header_media_url',
        'header_media_disk',
        'header_media_path',
        'header_media_name',
        'header_media_mime',
        'source_file_disk',
        'source_file_path',
        'source_file_name',
        'total_recipients',
        'valid_recipients',
        'invalid_recipients',
        'sent_count',
        'failed_count',
        'queued_at',
        'sent_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'body_mappings' => 'array',
            'button_mappings' => 'array',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConsoleTemplate::class, 'whatsapp_console_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsAppMarketingCampaignRecipient::class, 'whatsapp_marketing_campaign_id');
    }

    public function deliveryLogs(): HasMany
    {
        return $this->hasMany(WhatsAppMarketingCampaignDeliveryLog::class, 'whatsapp_marketing_campaign_id');
    }

    public function headerMediaAbsolutePath(): ?string
    {
        if (! filled($this->header_media_disk) || ! filled($this->header_media_path)) {
            return null;
        }

        return Storage::disk((string) $this->header_media_disk)->path((string) $this->header_media_path);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Borrador',
            self::STATUS_QUEUED => 'En cola',
            self::STATUS_SENDING => 'En envio',
            self::STATUS_COMPLETED => 'Completada',
            self::STATUS_CANCELLED => 'Cancelada',
            default => ucfirst((string) $this->status),
        };
    }
}
