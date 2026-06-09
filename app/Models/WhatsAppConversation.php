<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhatsAppConversation extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'whatsapp_conversations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'whatsapp_contact_id',
        'assigned_user_id',
        'status',
        'last_message_at',
        'archived_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Contact tied to this conversation.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(WhatsAppContact::class, 'whatsapp_contact_id');
    }

    /**
     * Optional internal user assigned to follow up this conversation.
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    /**
     * Messages in this conversation.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'whatsapp_conversation_id');
    }

    /**
     * Most recent message for conversation listing.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(WhatsAppMessage::class, 'whatsapp_conversation_id')->latestOfMany();
    }

    /**
     * Tags assigned to the conversation.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(
            WhatsAppTag::class,
            'whatsapp_conversation_tag',
            'whatsapp_conversation_id',
            'whatsapp_tag_id'
        )->withTimestamps();
    }
}
