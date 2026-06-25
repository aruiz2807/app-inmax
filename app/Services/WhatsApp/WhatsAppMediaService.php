<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMessageAttachment;
use App\Models\WhatsAppSetting;
use Illuminate\Http\UploadedFile;
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

    private const OUTBOUND_IMAGE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const OUTBOUND_AUDIO_MIME_TYPES = [
        'audio/aac',
        'audio/amr',
        'audio/mpeg',
        'audio/mp4',
        'audio/ogg',
    ];

    private const OUTBOUND_VIDEO_MIME_TYPES = [
        'video/mp4',
        'video/3gpp',
        'video/quicktime',
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
     * Persist the uploaded file locally and normalize its outbound metadata.
     *
     * @return array<string, mixed>
     */
    public function prepareOutboundAttachment(UploadedFile $file, ?string $caption = null): array
    {
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType() ?: 'application/octet-stream';
        $type = $this->resolveOutboundType($mimeType);
        $fileName = $this->sanitizeFileName($file->getClientOriginalName(), $type, $mimeType);
        $storagePath = $file->storeAs(
            'whatsapp/outbound/'.now()->format('Y/m/d'),
            Str::uuid().'_'.$fileName,
            'local'
        );

        if (! $storagePath) {
            throw new RuntimeException('No fue posible almacenar localmente el archivo a enviar.');
        }

        return [
            'provider_media_id' => null,
            'type' => $type,
            'mime_type' => $mimeType,
            'file_name' => $fileName,
            'caption' => filled($caption) ? trim((string) $caption) : null,
            'sha256' => hash_file('sha256', $file->getRealPath()) ?: null,
            'file_size_bytes' => $file->getSize(),
            'storage_disk' => 'local',
            'storage_path' => $storagePath,
            'download_status' => WhatsAppMessageAttachment::STATUS_DOWNLOADED,
            'downloaded_at' => now(),
            'last_download_attempt_at' => now(),
            'metadata' => [
                'origin' => 'outbound',
                'client_original_name' => $file->getClientOriginalName(),
                'client_extension' => $file->getClientOriginalExtension(),
            ],
        ];
    }

    /**
     * Upload a locally persisted media file to Meta and obtain its media id.
     *
     * @return array{ok: bool, status: int, data: array<string, mixed>}
     */
    public function uploadOutboundMedia(
        WhatsAppSetting $setting,
        string $storagePath,
        string $mimeType,
        string $fileName,
    ): array {
        $disk = Storage::disk('local');

        if (! $disk->exists($storagePath)) {
            throw new RuntimeException('El archivo local a subir no existe en storage.');
        }

        $response = Http::withToken($setting->access_token)
            ->attach('file', $disk->get($storagePath), $fileName, [
                'Content-Type' => $mimeType,
            ])
            ->post($this->mediaUploadEndpoint($setting), [
                'messaging_product' => 'whatsapp',
            ]);

        $responseData = $response->json();

        return [
            'ok' => $response->successful(),
            'status' => $response->status(),
            'data' => is_array($responseData) ? $responseData : [],
        ];
    }

    /**
     * Build the message payload for a media message using a Meta media id.
     *
     * @return array<string, mixed>
     */
    public function buildOutboundMessagePayload(
        string $to,
        array $attachmentData,
        string $providerMediaId,
    ): array {
        $type = (string) $attachmentData['type'];
        $caption = $attachmentData['caption'] ?? null;
        $fileName = $attachmentData['file_name'] ?? null;

        $mediaObject = array_filter([
            'id' => $providerMediaId,
            'caption' => in_array($type, ['image', 'video', 'document'], true) ? $caption : null,
            'filename' => $type === 'document' ? $fileName : null,
        ], fn (mixed $value): bool => filled($value));

        return [
            'messaging_product' => 'whatsapp',
            'to' => preg_replace('/\D+/', '', $to) ?? '',
            'type' => $type,
            $type => $mediaObject,
        ];
    }

    /**
     * Build a human-readable preview for outbound media messages.
     *
     * @param  array<string, mixed>  $attachmentData
     */
    public function buildOutboundPreview(array $attachmentData): string
    {
        $label = match ((string) ($attachmentData['type'] ?? 'document')) {
            'image' => '[Imagen]',
            'audio' => '[Audio]',
            'video' => '[Video]',
            default => '[Documento]',
        };

        $previewParts = array_filter([
            $label,
            $attachmentData['caption'] ?? null,
            $attachmentData['file_name'] ?? null,
        ]);

        return implode(' ', $previewParts);
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
     * Resolve the Graph endpoint for Meta media uploads.
     */
    private function mediaUploadEndpoint(WhatsAppSetting $setting): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/media',
            $setting->api_version,
            $setting->phone_number_id
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

    /**
     * Resolve the outbound WhatsApp message type from a mime type.
     */
    private function resolveOutboundType(string $mimeType): string
    {
        if (in_array($mimeType, self::OUTBOUND_IMAGE_MIME_TYPES, true)) {
            return 'image';
        }

        if (in_array($mimeType, self::OUTBOUND_AUDIO_MIME_TYPES, true)) {
            return 'audio';
        }

        if (in_array($mimeType, self::OUTBOUND_VIDEO_MIME_TYPES, true)) {
            return 'video';
        }

        return 'document';
    }

    /**
     * Sanitize client file names while preserving a valid extension.
     */
    private function sanitizeFileName(string $originalName, string $type, string $mimeType): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $name = pathinfo($originalName, PATHINFO_FILENAME);

        $sanitizedName = Str::slug($name);

        if ($sanitizedName === '') {
            $sanitizedName = $type.'-'.Str::random(6);
        }

        if ($extension === '') {
            $extensions = MimeTypes::getDefault()->getExtensions($mimeType);
            $extension = $extensions[0] ?? 'bin';
        }

        return $sanitizedName.'.'.Str::lower($extension);
    }
}
