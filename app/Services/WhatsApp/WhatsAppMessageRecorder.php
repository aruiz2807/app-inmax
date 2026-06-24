<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageAttachment;
use Carbon\Carbon;

class WhatsAppMessageRecorder
{
    public function __construct(
        private readonly WhatsAppContactService $contactService,
        private readonly WhatsAppMediaService $mediaService,
    ) {}

    /**
     * Persist an outbound template message and its initial delivery outcome.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $responseData
     */
    public function recordOutboundTemplate(
        string $to,
        string $templateName,
        string $languageCode,
        array $parameters,
        array $buttonUrlParameters,
        array $payload,
        array $responseData,
        bool $ok,
    ): WhatsAppMessage {
        return $this->recordOutboundMessage(
            to: $to,
            type: 'template',
            bodyText: $this->buildTemplatePreview($templateName, $parameters, $buttonUrlParameters),
            payload: $payload,
            responseData: $responseData,
            ok: $ok,
            templateName: $templateName,
            languageCode: $languageCode,
        );
    }

    /**
     * Persist an outbound free-form text message sent from the console.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $responseData
     */
    public function recordOutboundText(
        string $to,
        string $bodyText,
        array $payload,
        array $responseData,
        bool $ok,
    ): WhatsAppMessage {
        return $this->recordOutboundMessage(
            to: $to,
            type: 'text',
            bodyText: $bodyText,
            payload: $payload,
            responseData: $responseData,
            ok: $ok,
        );
    }

    /**
     * Persist an inbound user message received from the webhook.
     *
     * @param  array<string, mixed>  $messagePayload
     * @param  array<string, mixed>  $valuePayload
     */
    public function recordInboundMessage(array $messagePayload, array $valuePayload): WhatsAppMessage
    {
        $from = (string) data_get($messagePayload, 'from', data_get($valuePayload, 'contacts.0.wa_id', ''));
        $waId = (string) data_get($valuePayload, 'contacts.0.wa_id', $from);
        $name = data_get($valuePayload, 'contacts.0.profile.name');
        $contact = $this->contactService->findOrCreate($from, is_string($name) ? $name : null, $waId);
        $conversation = $this->findOrCreateConversation($contact->id);
        $occurredAt = $this->metaTimestamp(data_get($messagePayload, 'timestamp'));

        $message = WhatsAppMessage::query()->firstOrCreate(
            ['meta_message_id' => data_get($messagePayload, 'id')],
            [
                'whatsapp_conversation_id' => $conversation->id,
                'direction' => WhatsAppMessage::DIRECTION_INBOUND,
                'type' => (string) data_get($messagePayload, 'type', 'text'),
                'status' => 'received',
                'from_phone' => $contact->normalized_phone,
                'to_phone' => (string) data_get($valuePayload, 'metadata.display_phone_number'),
                'body_text' => $this->extractInboundText($messagePayload),
                'payload' => $messagePayload,
                'received_at' => $occurredAt,
            ]
        );

        if (! $message->statuses()->where('status', 'received')->exists()) {
            $message->statuses()->create([
                'status' => 'received',
                'meta_occurred_at' => $occurredAt,
                'payload' => $messagePayload,
            ]);
        }

        $attachment = $this->syncInboundAttachment($message, $messagePayload);

        if ($attachment) {
            $message->setRelation('primaryAttachment', $attachment);
        }

        $this->touchConversation($conversation, $occurredAt ?? now(), inbound: true);

        return $message;
    }

    /**
     * Persist a status transition received from Meta for a previously sent message.
     *
     * @param  array<string, mixed>  $statusPayload
     */
    public function recordStatusUpdate(array $statusPayload): ?WhatsAppMessage
    {
        $metaMessageId = (string) data_get($statusPayload, 'id');

        if ($metaMessageId === '') {
            return null;
        }

        $message = WhatsAppMessage::query()
            ->where('meta_message_id', $metaMessageId)
            ->first();

        if (! $message) {
            return null;
        }

        $status = (string) data_get($statusPayload, 'status', 'sent');
        $occurredAt = $this->metaTimestamp(data_get($statusPayload, 'timestamp')) ?? now();

        $message->forceFill([
            'status' => $status,
            'meta_conversation_id' => data_get($statusPayload, 'conversation.id'),
            'meta_pricing_category' => data_get($statusPayload, 'pricing.category'),
            'error_code' => data_get($statusPayload, 'errors.0.code')
                ? (string) data_get($statusPayload, 'errors.0.code')
                : $message->error_code,
            'error_message' => data_get($statusPayload, 'errors.0.title')
                ?? data_get($statusPayload, 'errors.0.message')
                ?? $message->error_message,
        ]);

        match ($status) {
            'sent' => $message->sent_at = $message->sent_at ?: $occurredAt,
            'delivered' => $message->delivered_at = $occurredAt,
            'read' => $message->read_at = $occurredAt,
            'failed' => $message->failed_at = $occurredAt,
            default => null,
        };

        $message->save();

        $message->statuses()->create([
            'status' => $status,
            'meta_occurred_at' => $occurredAt,
            'payload' => $statusPayload,
        ]);

        $this->touchConversation($message->conversation, $occurredAt, outbound: true);

        return $message->refresh();
    }

    /**
     * Create or reuse the active conversation for a contact.
     */
    private function findOrCreateConversation(int $contactId): WhatsAppConversation
    {
        return WhatsAppConversation::query()->firstOrCreate(
            ['whatsapp_contact_id' => $contactId],
            ['status' => 'open']
        );
    }

    /**
     * Update conversation and contact recency counters.
     */
    private function touchConversation(
        WhatsAppConversation $conversation,
        Carbon $timestamp,
        bool $inbound = false,
        bool $outbound = false
    ): void {
        $conversation->forceFill([
            'last_message_at' => $timestamp,
            'status' => 'open',
            'archived_at' => null,
        ])->save();

        $contact = $conversation->contact;

        $contact->forceFill([
            'last_message_at' => $timestamp,
            'last_inbound_at' => $inbound ? $timestamp : $contact->last_inbound_at,
            'last_outbound_at' => $outbound ? $timestamp : $contact->last_outbound_at,
            'unread_count' => $inbound ? $contact->unread_count + 1 : $contact->unread_count,
        ])->save();
    }

    /**
     * Create or update the attachment entity for supported inbound media messages.
     *
     * @param  array<string, mixed>  $messagePayload
     */
    private function syncInboundAttachment(WhatsAppMessage $message, array $messagePayload): ?WhatsAppMessageAttachment
    {
        $attachmentData = $this->mediaService->extractInboundAttachmentData($messagePayload);

        if (! $attachmentData) {
            return null;
        }

        $lookup = filled($attachmentData['provider_media_id'] ?? null)
            ? ['provider_media_id' => $attachmentData['provider_media_id']]
            : ['type' => $attachmentData['type']];

        return $message->attachments()->updateOrCreate($lookup, $attachmentData);
    }

    /**
     * Persist a generic outbound WhatsApp message and its first delivery state.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $responseData
     */
    private function recordOutboundMessage(
        string $to,
        string $type,
        string $bodyText,
        array $payload,
        array $responseData,
        bool $ok,
        ?string $templateName = null,
        ?string $languageCode = null,
    ): WhatsAppMessage {
        $contact = $this->contactService->findOrCreate($to);
        $conversation = $this->findOrCreateConversation($contact->id);
        $timestamp = now();

        $message = WhatsAppMessage::query()->create([
            'whatsapp_conversation_id' => $conversation->id,
            'meta_message_id' => data_get($responseData, 'messages.0.id'),
            'direction' => WhatsAppMessage::DIRECTION_OUTBOUND,
            'type' => $type,
            'status' => $ok ? 'sent' : 'failed',
            'to_phone' => $contact->normalized_phone,
            'template_name' => $templateName,
            'template_language_code' => $languageCode,
            'body_text' => $bodyText,
            'payload' => [
                'request' => $payload,
                'response' => $responseData,
            ],
            'sent_at' => $ok ? $timestamp : null,
            'failed_at' => $ok ? null : $timestamp,
            'error_code' => $ok ? null : (string) data_get($responseData, 'error.code', ''),
            'error_message' => $ok ? null : data_get($responseData, 'error.message'),
        ]);

        $message->statuses()->create([
            'status' => $message->status,
            'meta_occurred_at' => $timestamp,
            'payload' => $responseData,
        ]);

        $this->touchConversation($conversation, $timestamp, outbound: true);

        return $message;
    }

    /**
     * Build a human readable preview for outbound template messages.
     *
     * @param  array<int, string>  $parameters
     * @param  array<int, string>  $buttonUrlParameters
     */
    private function buildTemplatePreview(string $templateName, array $parameters, array $buttonUrlParameters): string
    {
        $previewParts = array_filter([
            '[Template] '.$templateName,
            ! empty($parameters) ? implode(' | ', $parameters) : null,
            ! empty($buttonUrlParameters) ? '[Button] '.implode(' | ', $buttonUrlParameters) : null,
        ]);

        return implode(' • ', $previewParts);
    }

    /**
     * Extract a text preview from inbound payloads.
     *
     * @param  array<string, mixed>  $messagePayload
     */
    private function extractInboundText(array $messagePayload): ?string
    {
        return match ((string) data_get($messagePayload, 'type', 'text')) {
            'text' => data_get($messagePayload, 'text.body'),
            'button' => data_get($messagePayload, 'button.text'),
            'interactive' => data_get($messagePayload, 'interactive.button_reply.title')
                ?? data_get($messagePayload, 'interactive.list_reply.title'),
            'image' => data_get($messagePayload, 'image.caption') ?: '[Imagen recibida]',
            'document' => data_get($messagePayload, 'document.caption') ?: '[Documento recibido]',
            'audio' => '[Audio recibido]',
            'video' => data_get($messagePayload, 'video.caption') ?: '[Video recibido]',
            'location' => '[Ubicacion recibida]',
            default => '['.ucfirst((string) data_get($messagePayload, 'type', 'mensaje')).' recibido]',
        };
    }

    /**
     * Convert Meta unix timestamps to Carbon instances.
     */
    private function metaTimestamp(mixed $value): ?Carbon
    {
        if (! is_scalar($value) || $value === '') {
            return null;
        }

        return Carbon::createFromTimestamp((int) $value, config('app.timezone'));
    }
}
