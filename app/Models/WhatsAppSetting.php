<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSetting extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'whatsapp_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'api_version',
        'phone_number_id',
        'access_token',
        'activation_template_name',
        'activation_language_code',
        'activation_body_parameters',
        'activation_button_parameters',
        'pin_reset_template_name',
        'pin_reset_language_code',
        'pin_reset_body_parameters',
        'pin_reset_button_parameters',
        'preregistration_template_name',
        'preregistration_language_code',
        'preregistration_body_parameters',
        'preregistration_button_parameters',
        'appointment_request_template_name',
        'appointment_request_language_code',
        'appointment_request_body_parameters',
        'appointment_request_button_parameters',
        'appointment_completed_template_name',
        'appointment_completed_language_code',
        'appointment_completed_body_parameters',
        'appointment_completed_button_parameters',
        'default_language',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'access_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'activation_body_parameters' => 'array',
            'activation_button_parameters' => 'array',
            'pin_reset_body_parameters' => 'array',
            'pin_reset_button_parameters' => 'array',
            'preregistration_body_parameters' => 'array',
            'preregistration_button_parameters' => 'array',
            'appointment_request_body_parameters' => 'array',
            'appointment_request_button_parameters' => 'array',
            'appointment_completed_body_parameters' => 'array',
            'appointment_completed_button_parameters' => 'array',
        ];
    }
}
