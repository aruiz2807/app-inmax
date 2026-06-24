<?php

namespace App\Http\Controllers;

use App\Models\WhatsAppMessageAttachment;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class WhatsAppMediaAttachmentController extends Controller
{
    /**
     * Stream the stored media inline for browser preview.
     */
    public function preview(WhatsAppMessageAttachment $attachment): BinaryFileResponse
    {
        [$disk, $path] = $this->resolveStorage($attachment);

        return response()->file($disk->path($path), [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$this->safeFileName($attachment).'"',
        ]);
    }

    /**
     * Download the stored media using the original file name when available.
     */
    public function download(WhatsAppMessageAttachment $attachment): BinaryFileResponse
    {
        [$disk, $path] = $this->resolveStorage($attachment);

        return response()->download(
            $disk->path($path),
            $this->safeFileName($attachment),
            [
                'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            ]
        );
    }

    /**
     * Resolve disk/path and guarantee the file exists before serving it.
     *
     * @return array{0: FilesystemAdapter, 1: string}
     */
    private function resolveStorage(WhatsAppMessageAttachment $attachment): array
    {
        $diskName = $attachment->storage_disk ?: 'local';
        $path = $attachment->storage_path;
        $disk = Storage::disk($diskName);

        abort_if(! filled($path) || ! $disk->exists($path), 404);

        return [$disk, $path];
    }

    /**
     * Produce a safe browser-facing file name.
     */
    private function safeFileName(WhatsAppMessageAttachment $attachment): string
    {
        return $attachment->file_name ?: 'whatsapp-media-'.$attachment->id;
    }
}
