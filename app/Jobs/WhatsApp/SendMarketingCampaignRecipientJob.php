<?php

namespace App\Jobs\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppMarketingCampaignRecipient;
use App\Services\WhatsApp\WhatsAppMarketingCampaignDeliveryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMarketingCampaignRecipientJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $recipientId,
        public ?int $sentByUserId = null,
    ) {}

    public function handle(WhatsAppMarketingCampaignDeliveryService $service): void
    {
        $recipient = WhatsAppMarketingCampaignRecipient::query()->find($this->recipientId);

        if (! $recipient) {
            return;
        }

        $sentBy = $this->sentByUserId ? User::query()->find($this->sentByUserId) : null;

        $service->sendRecipient($recipient, $sentBy);
    }
}
