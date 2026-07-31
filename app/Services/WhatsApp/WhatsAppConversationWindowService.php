<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppConversation;
use Carbon\CarbonInterface;

class WhatsAppConversationWindowService
{
    public function canSendFreeFormMessage(WhatsAppConversation $conversation): bool
    {
        return $this->expiresAt($conversation)?->isFuture() ?? false;
    }

    public function expiresAt(WhatsAppConversation $conversation): ?CarbonInterface
    {
        $lastInboundAt = $conversation->contact?->last_inbound_at;

        return $lastInboundAt?->copy()->addHours(24);
    }

    public function remainingMinutes(WhatsAppConversation $conversation): int
    {
        $expiresAt = $this->expiresAt($conversation);

        if (! $expiresAt || $expiresAt->isPast()) {
            return 0;
        }

        return (int) max(0, now()->diffInMinutes($expiresAt, false));
    }
}
