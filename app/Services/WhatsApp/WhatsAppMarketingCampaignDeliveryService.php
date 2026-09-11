<?php

namespace App\Services\WhatsApp;

use App\Models\User;
use App\Models\WhatsAppMarketingCampaign;
use App\Models\WhatsAppMarketingCampaignDeliveryLog;
use App\Models\WhatsAppMarketingCampaignRecipient;
use App\Models\WhatsAppSetting;
use Illuminate\Http\UploadedFile;

class WhatsAppMarketingCampaignDeliveryService
{
    public function __construct(
        private readonly WhatsAppCloudApiService $cloudApiService,
    ) {}

    /**
     * @return array{status: string, message: string}
     */
    public function sendRecipient(WhatsAppMarketingCampaignRecipient $recipient, ?User $sentBy = null): array
    {
        $recipient->loadMissing('campaign.template');
        $campaign = $recipient->campaign;
        $template = $campaign?->template;

        if (! $campaign || ! $template) {
            return $this->markFailed($recipient, 'La campaña no tiene plantilla configurada.', $sentBy);
        }

        if (! $template->is_active || ! $template->allow_marketing) {
            return $this->markFailed($recipient, 'La plantilla no esta activa para mercadotecnia.', $sentBy);
        }

        $setting = WhatsAppSetting::query()->first();

        if (! $setting || blank($setting->access_token) || blank($setting->phone_number_id)) {
            return $this->markFailed($recipient, 'La linea de WhatsApp no esta configurada.', $sentBy);
        }

        if (blank($recipient->phone_normalized)) {
            return $this->markFailed($recipient, 'El destinatario no tiene WhatsApp valido.', $sentBy);
        }

        $recipient->forceFill([
            'status' => WhatsAppMarketingCampaignRecipient::STATUS_QUEUED,
            'queued_at' => $recipient->queued_at ?? now(),
        ])->save();

        $campaign->forceFill([
            'status' => WhatsAppMarketingCampaign::STATUS_SENDING,
            'sent_at' => $campaign->sent_at ?? now(),
        ])->save();

        [$headerFile, $headerUrl] = $this->headerInput($campaign);

        $result = $this->cloudApiService->sendTemplateMessage(
            setting: $setting,
            to: (string) $recipient->phone_normalized,
            templateName: $template->meta_template_name,
            languageCode: $template->language_code,
            parameters: $recipient->body_values ?? [],
            buttonUrlParameters: $recipient->button_values ?? [],
            headerFile: $headerFile,
            headerMediaType: $template->header_media_type,
            headerMediaUrl: $headerUrl,
        );

        if (! $result['ok']) {
            return $this->markFailed(
                recipient: $recipient,
                message: data_get($result['data'], 'error.message', 'Meta rechazo el envio.'),
                sentBy: $sentBy,
                requestPayload: $result['payload'] ?? null,
                responsePayload: $result['data'] ?? null,
            );
        }

        $recipient->forceFill([
            'status' => WhatsAppMarketingCampaignRecipient::STATUS_SENT,
            'wamid' => data_get($result['data'], 'messages.0.id'),
            'error_message' => null,
            'sent_at' => now(),
        ])->save();

        $this->logDeliveryAttempt($recipient, $sentBy, 'success', $result['payload'] ?? null, $result['data'] ?? null);
        $this->refreshCampaignStatus($campaign);

        return ['status' => 'success', 'message' => 'Mensaje enviado correctamente.'];
    }

    public function refreshCampaignStatus(WhatsAppMarketingCampaign $campaign): void
    {
        $sent = $campaign->recipients()->where('status', WhatsAppMarketingCampaignRecipient::STATUS_SENT)->count();
        $failed = $campaign->recipients()->where('status', WhatsAppMarketingCampaignRecipient::STATUS_FAILED)->count();
        $pending = $campaign->recipients()->whereIn('status', [
            WhatsAppMarketingCampaignRecipient::STATUS_PENDING,
            WhatsAppMarketingCampaignRecipient::STATUS_QUEUED,
        ])->count();

        $campaign->forceFill([
            'sent_count' => $sent,
            'failed_count' => $failed,
            'status' => $pending === 0 ? WhatsAppMarketingCampaign::STATUS_COMPLETED : $campaign->status,
            'completed_at' => $pending === 0 ? ($campaign->completed_at ?? now()) : $campaign->completed_at,
        ])->save();
    }

    /**
     * @return array{0: ?UploadedFile, 1: ?string}
     */
    private function headerInput(WhatsAppMarketingCampaign $campaign): array
    {
        if ($campaign->header_media_source === 'url' && filled($campaign->header_media_url)) {
            return [null, (string) $campaign->header_media_url];
        }

        $path = $campaign->headerMediaAbsolutePath();

        if (! $path || ! file_exists($path)) {
            return [null, null];
        }

        return [
            new UploadedFile(
                path: $path,
                originalName: $campaign->header_media_name ?: basename($path),
                mimeType: $campaign->header_media_mime,
                test: true,
            ),
            null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $requestPayload
     * @param  array<string, mixed>|null  $responsePayload
     * @return array{status: string, message: string}
     */
    private function markFailed(
        WhatsAppMarketingCampaignRecipient $recipient,
        string $message,
        ?User $sentBy = null,
        ?array $requestPayload = null,
        ?array $responsePayload = null,
    ): array {
        $recipient->forceFill([
            'status' => WhatsAppMarketingCampaignRecipient::STATUS_FAILED,
            'error_message' => $message,
            'failed_at' => now(),
        ])->save();

        $this->logDeliveryAttempt($recipient, $sentBy, 'failed', $requestPayload, $responsePayload, $message);

        if ($recipient->campaign) {
            $this->refreshCampaignStatus($recipient->campaign);
        }

        return ['status' => 'failed', 'message' => $message];
    }

    /**
     * @param  array<string, mixed>|null  $requestPayload
     * @param  array<string, mixed>|null  $responsePayload
     */
    private function logDeliveryAttempt(
        WhatsAppMarketingCampaignRecipient $recipient,
        ?User $sentBy,
        string $status,
        ?array $requestPayload = null,
        ?array $responsePayload = null,
        ?string $errorMessage = null,
    ): void {
        $campaign = $recipient->campaign;
        $template = $campaign?->template;

        if (! $campaign) {
            return;
        }

        WhatsAppMarketingCampaignDeliveryLog::query()->create([
            'whatsapp_marketing_campaign_id' => $campaign->id,
            'whatsapp_marketing_campaign_recipient_id' => $recipient->id,
            'sent_by_user_id' => $sentBy?->id,
            'status' => $status,
            'recipient_phone' => $recipient->phone_normalized ?: $recipient->phone_raw,
            'template_snapshot' => $template ? [
                'id' => $template->id,
                'name' => $template->name,
                'meta_template_name' => $template->meta_template_name,
                'language_code' => $template->language_code,
                'body_variables' => $template->body_variables,
                'button_variables' => $template->button_variables,
                'header_media_type' => $template->header_media_type,
            ] : null,
            'request_payload' => $requestPayload,
            'response_payload' => $responsePayload ? json_encode($responsePayload, JSON_UNESCAPED_UNICODE) : null,
            'error_message' => $errorMessage,
            'sent_at' => now(),
        ]);
    }
}
