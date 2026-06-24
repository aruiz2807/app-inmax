<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessageAttachment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_DOWNLOADING = 'downloading';
    public const STATUS_DOWNLOADED = 'downloaded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNSUPPORTED = 'unsupported';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'whatsapp_message_attachments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'whatsapp_message_id',
        'provider_media_id',
        'type',
        'mime_type',
        'file_name',
        'caption',
        'sha256',
        'file_size_bytes',
        'storage_disk',
        'storage_path',
        'download_status',
        'downloaded_at',
        'last_download_attempt_at',
        'error_message',
        'metadata',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'downloaded_at' => 'datetime',
            'last_download_attempt_at' => 'datetime',
        ];
    }

    /**
     * Message the attachment belongs to.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }
}
