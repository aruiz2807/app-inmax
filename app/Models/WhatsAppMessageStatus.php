<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessageStatus extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'whatsapp_message_statuses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'whatsapp_message_id',
        'status',
        'meta_occurred_at',
        'payload',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta_occurred_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    /**
     * Message tied to this status change.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }
}
