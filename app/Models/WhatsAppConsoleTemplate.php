<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'name',
        'meta_template_name',
        'language_code',
        'example_text',
        'body_variables',
        'button_variables',
        'is_active',
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
        ];
    }
}
