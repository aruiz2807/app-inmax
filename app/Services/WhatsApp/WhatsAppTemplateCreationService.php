<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsAppTemplateCreationService
{
    /**
     * @param  array{name: string, language: string, category: string, header_type: string, header_text?: string|null, header_sample?: UploadedFile|null, body: string, footer?: string|null, header_examples?: string|null, body_examples?: string|null}  $data
     * @return array{ok: bool, message: string}
     */
    public function create(array $data): array
    {
        $setting = WhatsAppSetting::query()->first();

        if (! $setting || blank($setting->business_account_id) || blank($setting->access_token)) {
            return [
                'ok' => false,
                'message' => 'Guarda el Business Account ID y Access Token antes de crear plantillas.',
            ];
        }

        $headerHandle = null;

        if (in_array($data['header_type'], ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
            if (blank($setting->meta_app_id)) {
                return ['ok' => false, 'message' => 'Guarda el Meta App ID para crear plantillas con encabezado multimedia.'];
            }

            $file = $data['header_sample'] ?? null;

            if (! $file instanceof UploadedFile) {
                return ['ok' => false, 'message' => 'Carga la muestra multimedia del encabezado.'];
            }

            $headerHandle = $this->uploadHeaderSample($file, $setting);

            if ($headerHandle === null) {
                return ['ok' => false, 'message' => 'No se pudo cargar la muestra multimedia a Meta.'];
            }
        }

        $components = $this->components($data, $headerHandle);
        $payload = [
            'name' => $data['name'],
            'language' => $data['language'],
            'category' => $data['category'],
            'components' => $components,
        ];
        $url = "https://graph.facebook.com/{$setting->api_version}/{$setting->business_account_id}/message_templates";

        try {
            $response = Http::acceptJson()->timeout(30)->withToken($setting->access_token)->post($url, $payload);
        } catch (Throwable $exception) {
            report($exception);

            return ['ok' => false, 'message' => 'No se pudo conectar con Meta para crear la plantilla.'];
        }

        if (! $response->successful()) {
            $metaMessage = $response->json('error.message');
            $message = in_array($response->status(), [401, 403], true)
                ? 'Meta rechazo el token. Verifica el permiso whatsapp_business_management.'
                : 'Meta rechazo la plantilla.';

            return ['ok' => false, 'message' => filled($metaMessage) ? "{$message} {$metaMessage}" : $message];
        }

        $metaId = $response->json('id');

        if (blank($metaId)) {
            return ['ok' => false, 'message' => 'Meta no devolvio el identificador de la plantilla creada.'];
        }

        WhatsAppMessageTemplate::query()->updateOrCreate(
            ['meta_id' => (string) $metaId],
            [
                'name' => $data['name'],
                'language_code' => $data['language'],
                'status' => strtoupper((string) ($response->json('status') ?? 'PENDING')),
                'category' => $response->json('category') ?? $data['category'],
                'components' => $components,
                'last_synced_at' => now(),
            ]
        );

        return ['ok' => true, 'message' => 'Plantilla enviada a Meta para revision. Sincroniza despues para ver su estado actualizado.'];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array<string, mixed>>
     */
    private function components(array $data, ?string $headerHandle = null): array
    {
        $components = [];

        if ($data['header_type'] === 'TEXT') {
            $header = ['type' => 'HEADER', 'format' => 'TEXT', 'text' => $data['header_text']];
            $examples = $this->examples($data['header_examples'] ?? null);

            if ($examples !== [] && $this->hasVariables($data['header_text'] ?? null)) {
                $header['example'] = ['header_text' => $examples];
            }

            $components[] = $header;
        }

        if (in_array($data['header_type'], ['IMAGE', 'VIDEO', 'DOCUMENT'], true)) {
            $components[] = [
                'type' => 'HEADER',
                'format' => $data['header_type'],
                'example' => ['header_handle' => [$headerHandle]],
            ];
        }

        $body = ['type' => 'BODY', 'text' => $data['body']];
        $examples = $this->examples($data['body_examples'] ?? null);

        if ($examples !== [] && $this->hasVariables($data['body'])) {
            $body['example'] = ['body_text' => [$examples]];
        }

        $components[] = $body;

        if (filled($data['footer'] ?? null)) {
            $components[] = ['type' => 'FOOTER', 'text' => $data['footer']];
        }

        return $components;
    }

    /**
     * @return array<int, string>
     */
    private function examples(?string $value): array
    {
        return collect(explode('|', (string) $value))
            ->map(fn (string $example): string => trim($example))
            ->filter()
            ->values()
            ->all();
    }

    private function hasVariables(?string $text): bool
    {
        return preg_match('/{{[1-9][0-9]*}}/', (string) $text) === 1;
    }

    private function uploadHeaderSample(UploadedFile $file, WhatsAppSetting $setting): ?string
    {
        $query = http_build_query([
            'file_name' => $file->getClientOriginalName() ?: 'header-sample',
            'file_length' => $file->getSize(),
            'file_type' => $file->getMimeType() ?: 'application/octet-stream',
        ]);
        $startUrl = "https://graph.facebook.com/{$setting->api_version}/{$setting->meta_app_id}/uploads?{$query}";

        try {
            $start = Http::acceptJson()->timeout(30)->withToken($setting->access_token)->post($startUrl);

            if (! $start->successful() || blank($sessionId = $start->json('id'))) {
                return null;
            }

            $upload = Http::acceptJson()
                ->timeout(60)
                ->withToken($setting->access_token)
                ->withHeaders(['file_offset' => '0'])
                ->withBody($file->get(), 'application/octet-stream')
                ->post("https://graph.facebook.com/{$setting->api_version}/{$sessionId}");

            return $upload->successful() ? $upload->json('h') : null;
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }
}
