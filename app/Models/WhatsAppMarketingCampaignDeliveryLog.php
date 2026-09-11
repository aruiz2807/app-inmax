<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMarketingCampaignDeliveryLog extends Model
{
    protected $table = 'whatsapp_marketing_campaign_delivery_logs';

    protected $fillable = [
        'whatsapp_marketing_campaign_id',
        'whatsapp_marketing_campaign_recipient_id',
        'sent_by_user_id',
        'status',
        'recipient_phone',
        'template_snapshot',
        'request_payload',
        'response_payload',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'template_snapshot' => 'array',
            'request_payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMarketingCampaign::class, 'whatsapp_marketing_campaign_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMarketingCampaignRecipient::class, 'whatsapp_marketing_campaign_recipient_id');
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
