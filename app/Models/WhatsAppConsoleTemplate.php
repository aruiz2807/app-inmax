<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppConsoleTemplate extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'whatsapp_console_templates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'whatsapp_message_template_id',
        'name',
        'meta_template_name',
        'language_code',
        'example_text',
        'header_media_type',
        'body_variables',
        'button_variables',
        'is_active',
        'allow_console',
        'allow_marketing',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'body_variables' => 'array',
            'button_variables' => 'array',
            'is_active' => 'boolean',
            'allow_console' => 'boolean',
            'allow_marketing' => 'boolean',
        ];
    }

    public function metaTemplate(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessageTemplate::class, 'whatsapp_message_template_id');
    }
}
