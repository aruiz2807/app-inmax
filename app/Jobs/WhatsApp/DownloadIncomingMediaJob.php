<?php

namespace App\Jobs\WhatsApp;

use App\Models\WhatsAppMessageAttachment;
use App\Services\WhatsApp\WhatsAppMediaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DownloadIncomingMediaJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly int $attachmentId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(WhatsAppMediaService $mediaService): void
    {
        $attachment = WhatsAppMessageAttachment::query()->find($this->attachmentId);

        if (! $attachment) {
            return;
        }

        if ($attachment->download_status === WhatsAppMessageAttachment::STATUS_DOWNLOADED && filled($attachment->storage_path)) {
            return;
        }

        $attachment->forceFill([
            'download_status' => WhatsAppMessageAttachment::STATUS_DOWNLOADING,
            'last_download_attempt_at' => now(),
            'error_message' => null,
        ])->save();

        try {
            $mediaService->downloadInboundAttachment($attachment);
        } catch (Throwable $exception) {
            $attachment->forceFill([
                'download_status' => WhatsAppMessageAttachment::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'last_download_attempt_at' => now(),
            ])->save();

            Log::warning('WHATSAPP_MEDIA_DOWNLOAD_FAILED', [
                'attachment_id' => $attachment->id,
                'provider_media_id' => $attachment->provider_media_id,
                'type' => $attachment->type,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
