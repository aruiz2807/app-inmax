<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $api_version
 * @property string|null $phone_number_id
 * @property string|null $access_token
 * @property string|null $webhook_verify_token
 * @property string|null $app_secret
 * @property bool $webhook_enabled
 * @property \Illuminate\Support\Carbon|null $webhook_last_received_at
 * @property string|null $webhook_last_status
 * @property string|null $activation_template_name
 * @property string|null $system_user_activation_template_name
 * @property string|null $system_user_activation_language_code
 * @property array<array-key, mixed>|null $system_user_activation_body_parameters
 * @property array<array-key, mixed>|null $system_user_activation_button_parameters
 * @property string|null $activation_language_code
 * @property array<array-key, mixed>|null $activation_body_parameters
 * @property array<array-key, mixed>|null $activation_button_parameters
 * @property string|null $pin_reset_template_name
 * @property string|null $pin_reset_language_code
 * @property array<array-key, mixed>|null $pin_reset_body_parameters
 * @property array<array-key, mixed>|null $pin_reset_button_parameters
 * @property string|null $preregistration_template_name
 * @property string|null $preregistration_language_code
 * @property array<array-key, mixed>|null $preregistration_body_parameters
 * @property array<array-key, mixed>|null $preregistration_button_parameters
 * @property string|null $appointment_request_template_name
 * @property string|null $appointment_request_language_code
 * @property array<array-key, mixed>|null $appointment_request_body_parameters
 * @property array<array-key, mixed>|null $appointment_request_button_parameters
 * @property string|null $appointment_completed_template_name
 * @property string|null $appointment_completed_language_code
 * @property array<array-key, mixed>|null $appointment_completed_body_parameters
 * @property array<array-key, mixed>|null $appointment_completed_button_parameters
 * @property string $default_language
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereActivationBodyParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereActivationButtonParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereActivationLanguageCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereActivationTemplateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereApiVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereAppointmentCompletedBodyParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereAppointmentCompletedButtonParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereAppointmentCompletedLanguageCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereAppointmentCompletedTemplateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereAppointmentRequestBodyParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereAppointmentRequestButtonParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereAppointmentRequestLanguageCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereAppointmentRequestTemplateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereDefaultLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting wherePhoneNumberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting wherePinResetBodyParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting wherePinResetButtonParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting wherePinResetLanguageCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting wherePinResetTemplateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting wherePreregistrationBodyParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting wherePreregistrationButtonParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting wherePreregistrationLanguageCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting wherePreregistrationTemplateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereSystemUserActivationBodyParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereSystemUserActivationButtonParameters($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereSystemUserActivationLanguageCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereSystemUserActivationTemplateName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WhatsAppSetting whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
        'webhook_verify_token',
        'app_secret',
        'webhook_enabled',
        'webhook_last_received_at',
        'webhook_last_status',
        'activation_template_name',
        'system_user_activation_template_name',
        'system_user_activation_language_code',
        'system_user_activation_body_parameters',
        'system_user_activation_button_parameters',
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
        'webhook_verify_token',
        'app_secret',
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
            'webhook_verify_token' => 'encrypted',
            'app_secret' => 'encrypted',
            'webhook_enabled' => 'boolean',
            'webhook_last_received_at' => 'datetime',
            'system_user_activation_body_parameters' => 'array',
            'system_user_activation_button_parameters' => 'array',
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
