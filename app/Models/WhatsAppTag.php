<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WhatsAppTag extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'whatsapp_tags';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'color',
    ];

    /**
     * Conversations associated to this tag.
     */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(
            WhatsAppConversation::class,
            'whatsapp_conversation_tag',
            'whatsapp_tag_id',
            'whatsapp_conversation_id'
        )->withTimestamps();
    }
}
