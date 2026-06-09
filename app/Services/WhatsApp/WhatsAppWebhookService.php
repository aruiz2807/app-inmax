<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppSetting;
use App\Models\WhatsAppWebhookEvent;

class WhatsAppWebhookService
{
    public function __construct(
        private readonly WhatsAppMessageRecorder $messageRecorder,
    ) {}

    /**
     * Verify Meta signature using the configured app secret.
     */
    public function hasValidSignature(string $rawPayload, ?string $signatureHeader, ?string $appSecret): bool
    {
        if (! filled($signatureHeader) || ! filled($appSecret)) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $rawPayload, $appSecret);

        return hash_equals($expected, (string) $signatureHeader);
    }

    /**
     * Persist and process a webhook payload in an idempotent way.
     *
     * @param  array<string, mixed>  $payload
     */
    public function ingest(array $payload, bool $signatureValid): WhatsAppWebhookEvent
    {
        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
        $eventHash = hash('sha256', $encodedPayload);

        $event = WhatsAppWebhookEvent::query()->firstOrCreate(
            ['event_hash' => $eventHash],
            [
                'meta_object' => data_get($payload, 'object'),
                'event_type' => $this->detectEventType($payload),
                'signature_valid' => $signatureValid,
                'payload' => $payload,
            ]
        );

        if ($event->processed_at !== null || ! $signatureValid) {
            return $event;
        }

        foreach ((array) data_get($payload, 'entry', []) as $entry) {
            foreach ((array) data_get($entry, 'changes', []) as $change) {
                $value = (array) data_get($change, 'value', []);

                foreach ((array) data_get($value, 'messages', []) as $messagePayload) {
                    if (is_array($messagePayload)) {
                        $this->messageRecorder->recordInboundMessage($messagePayload, $value);
                    }
                }

                foreach ((array) data_get($value, 'statuses', []) as $statusPayload) {
                    if (is_array($statusPayload)) {
                        $this->messageRecorder->recordStatusUpdate($statusPayload);
                    }
                }
            }
        }

        $event->forceFill([
            'processed_at' => now(),
        ])->save();

        $this->updateWebhookStatus('ok');

        return $event->refresh();
    }

    /**
     * Classify payload type for audit/debug.
     *
     * @param  array<string, mixed>  $payload
     */
    private function detectEventType(array $payload): string
    {
        $entry = (array) data_get($payload, 'entry.0.changes.0.value', []);

        return match (true) {
            ! empty(data_get($entry, 'messages', [])) && ! empty(data_get($entry, 'statuses', [])) => 'mixed',
            ! empty(data_get($entry, 'messages', [])) => 'message',
            ! empty(data_get($entry, 'statuses', [])) => 'status',
            default => 'unknown',
        };
    }

    /**
     * Persist the last webhook heartbeat on the singleton settings row.
     */
    public function updateWebhookStatus(string $status): void
    {
        $setting = WhatsAppSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'api_version' => 'v22.0',
                'default_language' => 'es_MX',
            ]
        );

        $setting->forceFill([
            'webhook_last_received_at' => now(),
            'webhook_last_status' => $status,
        ])->save();
    }
}
