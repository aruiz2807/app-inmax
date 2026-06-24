<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppCloudApiService
{
    public function __construct(
        private readonly WhatsAppMessageRecorder $messageRecorder,
        private readonly WhatsAppMediaService $mediaService,
    ) {}

    /**
     * Send a template message using WhatsApp Cloud API.
     *
     * @param  array<int, string>  $parameters
     * @param  array<int, string>  $buttonUrlParameters
     * @return array{ok: bool, status: int, data: array<string, mixed>, payload: array<string, mixed>}
     */
    public function sendTemplateMessage(
        WhatsAppSetting $setting,
        string $to,
        string $templateName,
        string $languageCode,
        array $parameters = [],
        array $buttonUrlParameters = [],
    ): array {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($to),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
            ],
        ];

        $components = [];

        if (! empty($parameters)) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $param) => ['type' => 'text', 'text' => $param],
                    $parameters
                ),
            ];
        }

        if (! empty($buttonUrlParameters)) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => array_map(
                    fn (string $param) => ['type' => 'text', 'text' => $param],
                    $buttonUrlParameters
                ),
            ];
        }

        if (! empty($components)) {
            $payload['template']['components'] = $components;
        }

        $result = $this->dispatchMessage($setting, $payload);

        Log::info('WHATSAPP_TEMPLATE_SEND', [
            'endpoint' => $this->endpoint($setting),
            'status' => $result['status'],
            'template' => $templateName,
            'language_code' => $languageCode,
            'to' => $payload['to'],
            'ok' => $result['ok'],
            'response' => $result['data'],
        ]);

        try {
            $this->messageRecorder->recordOutboundTemplate(
                to: $payload['to'],
                templateName: $templateName,
                languageCode: $languageCode,
                parameters: $parameters,
                buttonUrlParameters: $buttonUrlParameters,
                payload: $payload,
                responseData: $result['data'],
                ok: $result['ok'],
            );
        } catch (Throwable $exception) {
            Log::warning('WHATSAPP_MESSAGE_RECORD_FAILED', [
                'type' => 'template',
                'template' => $templateName,
                'to' => $payload['to'],
                'error' => $exception->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Send a free-form text message using WhatsApp Cloud API.
     *
     * @return array{ok: bool, status: int, data: array<string, mixed>, payload: array<string, mixed>}
     */
    public function sendTextMessage(
        WhatsAppSetting $setting,
        string $to,
        string $body,
    ): array {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $this->normalizePhone($to),
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $body,
            ],
        ];

        $result = $this->dispatchMessage($setting, $payload);

        Log::info('WHATSAPP_TEXT_SEND', [
            'endpoint' => $this->endpoint($setting),
            'status' => $result['status'],
            'to' => $payload['to'],
            'ok' => $result['ok'],
            'response' => $result['data'],
        ]);

        try {
            $this->messageRecorder->recordOutboundText(
                to: $payload['to'],
                bodyText: $body,
                payload: $payload,
                responseData: $result['data'],
                ok: $result['ok'],
            );
        } catch (Throwable $exception) {
            Log::warning('WHATSAPP_MESSAGE_RECORD_FAILED', [
                'type' => 'text',
                'to' => $payload['to'],
                'error' => $exception->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Send a media message using an uploaded file and persist it locally.
     *
     * @return array{
     *   ok: bool,
     *   status: int,
     *   data: array<string, mixed>,
     *   payload: array<string, mixed>,
     *   upload: array{ok: bool, status: int, data: array<string, mixed>},
     *   attachment: array<string, mixed>
     * }
     */
    public function sendMediaMessage(
        WhatsAppSetting $setting,
        string $to,
        UploadedFile $file,
        ?string $caption = null,
    ): array {
        $attachmentData = $this->mediaService->prepareOutboundAttachment($file, $caption);
        $uploadResult = $this->mediaService->uploadOutboundMedia(
            setting: $setting,
            storagePath: (string) $attachmentData['storage_path'],
            mimeType: (string) $attachmentData['mime_type'],
            fileName: (string) $attachmentData['file_name'],
        );

        if (! $uploadResult['ok']) {
            $failedResult = [
                'ok' => false,
                'status' => $uploadResult['status'],
                'data' => $uploadResult['data'],
                'payload' => [
                    'upload' => [
                        'storage_path' => $attachmentData['storage_path'],
                        'mime_type' => $attachmentData['mime_type'],
                        'file_name' => $attachmentData['file_name'],
                    ],
                ],
                'upload' => $uploadResult,
                'attachment' => $attachmentData,
            ];

            $this->recordOutboundMediaAttempt($to, $attachmentData, $failedResult);

            return $failedResult;
        }

        $providerMediaId = (string) data_get($uploadResult['data'], 'id', '');

        if ($providerMediaId === '') {
            $failedResult = [
                'ok' => false,
                'status' => $uploadResult['status'],
                'data' => [
                    'error' => [
                        'message' => 'Meta no devolvio media_id despues del upload.',
                    ],
                ],
                'payload' => [
                    'upload' => [
                        'storage_path' => $attachmentData['storage_path'],
                        'mime_type' => $attachmentData['mime_type'],
                        'file_name' => $attachmentData['file_name'],
                    ],
                ],
                'upload' => $uploadResult,
                'attachment' => $attachmentData,
            ];

            $this->recordOutboundMediaAttempt($to, $attachmentData, $failedResult);

            return $failedResult;
        }

        $attachmentData['provider_media_id'] = $providerMediaId !== '' ? $providerMediaId : null;
        $payload = $this->mediaService->buildOutboundMessagePayload($to, $attachmentData, $providerMediaId);
        $result = $this->dispatchMessage($setting, $payload);

        Log::info('WHATSAPP_MEDIA_SEND', [
            'endpoint' => $this->endpoint($setting),
            'upload_endpoint' => sprintf(
                'https://graph.facebook.com/%s/%s/media',
                $setting->api_version,
                $setting->phone_number_id
            ),
            'status' => $result['status'],
            'type' => $attachmentData['type'],
            'mime_type' => $attachmentData['mime_type'],
            'file_name' => $attachmentData['file_name'],
            'to' => $payload['to'],
            'ok' => $result['ok'],
            'response' => $result['data'],
            'upload_response' => $uploadResult['data'],
        ]);

        $finalResult = [
            'ok' => $result['ok'],
            'status' => $result['status'],
            'data' => $result['data'],
            'payload' => $payload,
            'upload' => $uploadResult,
            'attachment' => $attachmentData,
        ];

        $this->recordOutboundMediaAttempt($to, $attachmentData, $finalResult);

        return $finalResult;
    }

    /**
     * Dispatch a WhatsApp message payload through Meta Cloud API.
     *
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, status: int, data: array<string, mixed>, payload: array<string, mixed>}
     */
    private function dispatchMessage(WhatsAppSetting $setting, array $payload): array
    {
        $response = Http::acceptJson()
            ->withToken($setting->access_token)
            ->post($this->endpoint($setting), $payload);

        $responseData = $response->json();

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => is_array($responseData) ? $responseData : [],
            'payload' => $payload,
        ];
    }

    /**
     * Resolve the Graph API endpoint for outbound WhatsApp messages.
     */
    private function endpoint(WhatsAppSetting $setting): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $setting->api_version,
            $setting->phone_number_id
        );
    }

    /**
     * Normalize phone into a WhatsApp API-compatible format (digits only).
     */
    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }

    /**
     * Persist the outbound media attempt without duplicating cloud-service flow control.
     *
     * @param  array<string, mixed>  $attachmentData
     * @param  array{
     *   ok: bool,
     *   status: int,
     *   data: array<string, mixed>,
     *   payload: array<string, mixed>,
     *   upload?: array{ok: bool, status: int, data: array<string, mixed>}
     * }  $result
     */
    private function recordOutboundMediaAttempt(string $to, array $attachmentData, array $result): void
    {
        try {
            $this->messageRecorder->recordOutboundMedia(
                to: $this->normalizePhone($to),
                type: (string) $attachmentData['type'],
                bodyText: $this->mediaService->buildOutboundPreview($attachmentData),
                payload: [
                    'message' => $result['payload'],
                    'upload' => $result['upload'] ?? null,
                ],
                responseData: $result['data'],
                ok: $result['ok'],
                attachmentData: $attachmentData,
            );
        } catch (Throwable $exception) {
            Log::warning('WHATSAPP_MESSAGE_RECORD_FAILED', [
                'type' => $attachmentData['type'] ?? 'media',
                'to' => $this->normalizePhone($to),
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
