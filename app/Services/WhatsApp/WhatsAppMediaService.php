<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMessageAttachment;
use App\Models\WhatsAppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Mime\MimeTypes;

class WhatsAppMediaService
{
    private const SUPPORTED_INBOUND_TYPES = [
        'image',
        'document',
        'audio',
        'video',
    ];

    /**
     * Extract normalized attachment attributes from an inbound Meta message payload.
     *
     * @param  array<string, mixed>  $messagePayload
     * @return array<string, mixed>|null
     */
    public function extractInboundAttachmentData(array $messagePayload): ?array
    {
        $type = (string) data_get($messagePayload, 'type', '');

        if (! in_array($type, self::SUPPORTED_INBOUND_TYPES, true)) {
            return null;
        }

        $mediaPayload = data_get($messagePayload, $type);

        if (! is_array($mediaPayload)) {
            return null;
        }

        $providerMediaId = data_get($mediaPayload, 'id');
        $mimeType = data_get($mediaPayload, 'mime_type');
        $fileName = data_get($mediaPayload, 'filename');
        $caption = data_get($mediaPayload, 'caption');
        $sha256 = data_get($mediaPayload, 'sha256');

        return [
            'provider_media_id' => is_string($providerMediaId) && $providerMediaId !== '' ? $providerMediaId : null,
            'type' => $type,
            'mime_type' => is_string($mimeType) && $mimeType !== '' ? $mimeType : null,
            'file_name' => is_string($fileName) && $fileName !== '' ? $fileName : null,
            'caption' => is_string($caption) && $caption !== '' ? $caption : null,
            'sha256' => is_string($sha256) && $sha256 !== '' ? $sha256 : null,
            'download_status' => filled($providerMediaId)
                ? WhatsAppMessageAttachment::STATUS_PENDING
                : WhatsAppMessageAttachment::STATUS_UNSUPPORTED,
            'metadata' => [
                'provider_payload' => $mediaPayload,
                'voice' => (bool) data_get($mediaPayload, 'voice', false),
            ],
        ];
    }

    /**
     * Download and store an inbound attachment referenced by Meta media id.
     */
    public function downloadInboundAttachment(WhatsAppMessageAttachment $attachment): WhatsAppMessageAttachment
    {
        $setting = WhatsAppSetting::query()->first();

        if (! $setting || ! filled($setting->access_token) || ! filled($setting->phone_number_id)) {
            throw new RuntimeException('Falta la configuracion API de WhatsApp para descargar multimedia.');
        }

        if (! filled($attachment->provider_media_id)) {
            throw new RuntimeException('El adjunto no contiene media_id de Meta.');
        }

        $metadataResponse = Http::acceptJson()
            ->withToken($setting->access_token)
            ->get($this->mediaMetadataEndpoint($setting, $attachment->provider_media_id), [
                'phone_number_id' => $setting->phone_number_id,
            ]);

        if (! $metadataResponse->successful()) {
            throw new RuntimeException(sprintf(
                'Meta rechazo la consulta del media_id %s con status %s.',
                $attachment->provider_media_id,
                $metadataResponse->status()
            ));
        }

        $metadata = $metadataResponse->json();

        if (! is_array($metadata) || ! filled(data_get($metadata, 'url'))) {
            throw new RuntimeException('Meta no devolvio una URL valida para la descarga del adjunto.');
        }

        $downloadResponse = Http::withToken($setting->access_token)
            ->get((string) data_get($metadata, 'url'));

        if (! $downloadResponse->successful()) {
            throw new RuntimeException(sprintf(
                'Meta no permitio descargar el adjunto %s. HTTP %s.',
                $attachment->provider_media_id,
                $downloadResponse->status()
            ));
        }

        $mimeType = (string) (data_get($metadata, 'mime_type') ?: $attachment->mime_type ?: $downloadResponse->header('Content-Type'));
        $fileName = $attachment->file_name ?: $this->defaultFileName($attachment, $mimeType);
        $storagePath = $this->buildStoragePath($attachment, $fileName);

        Storage::disk('local')->put($storagePath, $downloadResponse->body());

        $attachment->forceFill([
            'mime_type' => $mimeType !== '' ? $mimeType : $attachment->mime_type,
            'file_name' => $fileName,
            'sha256' => data_get($metadata, 'sha256') ?: $attachment->sha256,
            'file_size_bytes' => data_get($metadata, 'file_size') ?: $attachment->file_size_bytes,
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'download_status' => WhatsAppMessageAttachment::STATUS_DOWNLOADED,
            'downloaded_at' => now(),
            'last_download_attempt_at' => now(),
            'error_message' => null,
            'metadata' => array_merge($attachment->metadata ?? [], [
                'provider_metadata' => [
                    'mime_type' => data_get($metadata, 'mime_type'),
                    'sha256' => data_get($metadata, 'sha256'),
                    'file_size' => data_get($metadata, 'file_size'),
                    'messaging_product' => data_get($metadata, 'messaging_product'),
                ],
            ]),
        ])->save();

        return $attachment->refresh();
    }

    /**
     * Determine the Graph endpoint for media metadata retrieval.
     */
    private function mediaMetadataEndpoint(WhatsAppSetting $setting, string $providerMediaId): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s',
            $setting->api_version,
            $providerMediaId
        );
    }

    /**
     * Build a stable storage path under local disk for inbound media.
     */
    private function buildStoragePath(WhatsAppMessageAttachment $attachment, string $fileName): string
    {
        $directory = sprintf(
            'whatsapp/inbound/%s/message-%s',
            now()->format('Y/m/d'),
            $attachment->whatsapp_message_id
        );

        return $directory.'/'.$attachment->id.'_'.$fileName;
    }

    /**
     * Resolve a deterministic fallback file name from mime type and message metadata.
     */
    private function defaultFileName(WhatsAppMessageAttachment $attachment, string $mimeType): string
    {
        $baseName = Str::slug($attachment->type.'-'.$attachment->id);
        $extensions = $mimeType !== '' ? MimeTypes::getDefault()->getExtensions($mimeType) : [];
        $extension = $extensions[0] ?? match ($attachment->type) {
            'image' => 'jpg',
            'audio' => 'ogg',
            'video' => 'mp4',
            'document' => 'bin',
            default => 'bin',
        };

        return $baseName.'.'.$extension;
    }
}
