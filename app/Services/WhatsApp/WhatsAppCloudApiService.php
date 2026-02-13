<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppCloudApiService
{
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

        $endpoint = sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            $setting->api_version,
            $setting->phone_number_id
        );

        $response = Http::acceptJson()
            ->withToken($setting->access_token)
            ->post($endpoint, $payload);

        $responseData = $response->json();

        Log::info('WHATSAPP_TEMPLATE_SEND', [
            'endpoint' => $endpoint,
            'status' => $response->status(),
            'template' => $templateName,
            'language_code' => $languageCode,
            'to' => $payload['to'],
            'ok' => $response->successful(),
            'response' => $responseData,
        ]);

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => is_array($responseData) ? $responseData : [],
            'payload' => $payload,
        ];
    }

    /**
     * Normalize phone into a WhatsApp API-compatible format (digits only).
     */
    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? '';
    }
}
