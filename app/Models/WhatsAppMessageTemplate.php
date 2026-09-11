<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppMessageTemplate extends Model
{
    protected $table = 'whatsapp_message_templates';

    protected $fillable = [
        'meta_id',
        'name',
        'language_code',
        'status',
        'category',
        'components',
        'is_active',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'components' => 'array',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function consoleTemplates(): HasMany
    {
        return $this->hasMany(WhatsAppConsoleTemplate::class, 'whatsapp_message_template_id');
    }

    public function isApproved(): bool
    {
        return strtoupper((string) $this->status) === 'APPROVED';
    }
}
