<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhatsAppMessage extends Model
{
    public const DIRECTION_INBOUND = 'inbound';
    public const DIRECTION_OUTBOUND = 'outbound';

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'whatsapp_messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'whatsapp_conversation_id',
        'meta_message_id',
        'direction',
        'type',
        'status',
        'from_phone',
        'to_phone',
        'template_name',
        'template_language_code',
        'body_text',
        'meta_conversation_id',
        'meta_pricing_category',
        'error_code',
        'error_message',
        'payload',
        'sent_at',
        'delivered_at',
        'read_at',
        'failed_at',
        'received_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
            'failed_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    /**
     * Conversation the message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'whatsapp_conversation_id');
    }

    /**
     * Status history entries for this message.
     */
    public function statuses(): HasMany
    {
        return $this->hasMany(WhatsAppMessageStatus::class, 'whatsapp_message_id');
    }

    /**
     * Attachments stored for this message.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(WhatsAppMessageAttachment::class, 'whatsapp_message_id');
    }

    /**
     * Primary attachment for single-media WhatsApp messages.
     */
    public function primaryAttachment(): HasOne
    {
        return $this->hasOne(WhatsAppMessageAttachment::class, 'whatsapp_message_id')->latestOfMany();
    }
}
