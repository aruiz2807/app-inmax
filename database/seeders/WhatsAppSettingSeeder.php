<?php

namespace Database\Seeders;

use App\Models\WhatsAppSetting;
use Illuminate\Database\Seeder;

class WhatsAppSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WhatsAppSetting::truncate();

        WhatsAppSetting::create([
            'api_version' => 'v22.0',
            'phone_number_id' => '953464767857532',
            'activation_template_name' => 'confirmacion_registro',
            'system_user_activation_template_name' => 'registro_usuario',
            'system_user_activation_language_code' => 'es',
            'system_user_activation_body_parameters' => ['user_name'],
            'system_user_activation_button_parameters' => ['pin_token'],
            'activation_language_code' => 'es',
            'activation_body_parameters' => ['user_name', 'policy_number', 'start_date'],
            'activation_button_parameters' => ['pin_token'],
            'pin_reset_template_name' => 'recuperacion_acceso',
            'pin_reset_language_code' => 'es',
            'pin_reset_body_parameters' => ['user_name'],
            'pin_reset_button_parameters' => ['pin_token'],
            'preregistration_template_name' => ' registro',
            'preregistration_language_code' => 'es',
            'preregistration_body_parameters' => ['promoter_name'],
            'preregistration_button_parameters' => ['preregistration_token'],
            'appointment_request_template_name' => 'aviso_cita',
            'appointment_request_language_code' => 'es',
            'appointment_request_body_parameters' => ['member_name', 'appointment_date', 'appointment_time'],
            'appointment_request_button_parameters' => [],
            'appointment_completed_template_name' => 'servicio_completado',
            'appointment_completed_language_code' => 'es',
            'appointment_completed_body_parameters' => ['member_name', 'completed_date', 'doctor_name'],
            'appointment_completed_button_parameters' => [],
            'default_language' => 'es',
        ]);
    }
}
