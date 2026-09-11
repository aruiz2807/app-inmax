<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppSetting;
use Illuminate\Support\Facades\Http;
use Throwable;

class WhatsAppTemplateSyncService
{
    /**
     * @return array{ok: bool, message: string}
     */
    public function sync(): array
    {
        $setting = WhatsAppSetting::query()->first();

        if (! $setting || blank($setting->business_account_id) || blank($setting->access_token)) {
            return [
                'ok' => false,
                'message' => 'Guarda el Business Account ID y Access Token antes de sincronizar plantillas.',
            ];
        }

        $url = "https://graph.facebook.com/{$setting->api_version}/{$setting->business_account_id}/message_templates";
        $templates = [];

        try {
            do {
                $response = Http::acceptJson()
                    ->timeout(30)
                    ->withToken($setting->access_token)
                    ->get($url, [
                        'fields' => 'id,name,language,status,category,components',
                        'limit' => 100,
                    ]);

                if (! $response->successful()) {
                    return [
                        'ok' => false,
                        'message' => in_array($response->status(), [401, 403], true)
                            ? 'Meta rechazo el token. Verifica permisos de administracion de plantillas.'
                            : 'Meta no permitio consultar plantillas. Revisa Business Account ID, API version y token.',
                    ];
                }

                $data = $response->json('data') ?? [];
                $templates = [...$templates, ...$data];
                $url = $response->json('paging.next');
            } while (filled($url));
        } catch (Throwable $exception) {
            report($exception);

            return ['ok' => false, 'message' => 'No se pudo conectar con Meta para sincronizar plantillas.'];
        }

        foreach ($templates as $template) {
            if (blank($template['id'] ?? null) || blank($template['name'] ?? null)) {
                continue;
            }

            WhatsAppMessageTemplate::query()->updateOrCreate(
                ['meta_id' => (string) $template['id']],
                [
                    'name' => (string) $template['name'],
                    'language_code' => (string) ($template['language'] ?? 'es'),
                    'status' => strtoupper((string) ($template['status'] ?? 'UNKNOWN')),
                    'category' => $template['category'] ?? null,
                    'components' => $template['components'] ?? [],
                    'last_synced_at' => now(),
                ]
            );
        }

        $approved = collect($templates)
            ->filter(fn (array $template): bool => strtoupper((string) ($template['status'] ?? '')) === 'APPROVED')
            ->count();

        return [
            'ok' => true,
            'message' => count($templates)." plantilla(s) sincronizada(s); {$approved} aprobada(s).",
        ];
    }
}
