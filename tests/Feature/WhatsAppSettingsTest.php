<?php

namespace Tests\Feature;

use App\Livewire\Settings\WhatsAppSettingsPage;
use App\Models\User;
use App\Models\WhatsAppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class WhatsAppSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_whatsapp_settings_page(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get(route('settings.whatsapp'));

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_whatsapp_settings_page(): void
    {
        $user = User::factory()->create([
            'profile' => 'User',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('settings.whatsapp'));

        $response->assertStatus(403);
    }

    public function test_admin_can_save_whatsapp_settings(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(WhatsAppSettingsPage::class)
            ->set('apiVersion', 'v22.0')
            ->set('phoneNumberId', '113206948334320')
            ->set('accessToken', 'meta_test_token_12345')
            ->set('activationTemplateName', 'activation_pin_template')
            ->set('activationLanguageCode', 'es_MX')
            ->set('activationBodyParameters', ['user_name', 'policy_number', 'sales_user_name'])
            ->set('activationButtonParameters', ['pin_token'])
            ->set('pinResetTemplateName', 'reset_pin_template')
            ->set('pinResetLanguageCode', 'es')
            ->set('pinResetBodyParameters', ['user_name'])
            ->set('pinResetButtonParameters', ['pin_token'])
            ->set('preregistrationTemplateName', 'policy_preregistration_template')
            ->set('preregistrationLanguageCode', 'en_US')
            ->set('preregistrationBodyParameters', ['promoter_name', 'plan_name'])
            ->set('preregistrationButtonParameters', ['preregistration_token'])
            ->set('appointmentRequestTemplateName', 'appointment_request_template')
            ->set('appointmentRequestLanguageCode', 'es_MX')
            ->set('appointmentRequestBodyParameters', ['member_name', 'appointment_date', 'appointment_time'])
            ->set('appointmentRequestButtonParameters', [])
            ->set('appointmentCompletedTemplateName', 'appointment_completed_template')
            ->set('appointmentCompletedLanguageCode', 'en')
            ->set('appointmentCompletedBodyParameters', ['member_name', 'completed_date', 'doctor_name'])
            ->set('appointmentCompletedButtonParameters', [])
            ->set('defaultLanguage', 'es_MX')
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('whatsapp_settings', [
            'api_version' => 'v22.0',
            'phone_number_id' => '113206948334320',
            'activation_template_name' => 'activation_pin_template',
            'activation_language_code' => 'es_MX',
            'pin_reset_template_name' => 'reset_pin_template',
            'pin_reset_language_code' => 'es',
            'preregistration_template_name' => 'policy_preregistration_template',
            'preregistration_language_code' => 'en_US',
            'appointment_request_template_name' => 'appointment_request_template',
            'appointment_request_language_code' => 'es_MX',
            'appointment_completed_template_name' => 'appointment_completed_template',
            'appointment_completed_language_code' => 'en',
            'default_language' => 'es_MX',
        ]);

        $setting = WhatsAppSetting::query()->firstOrFail();

        $this->assertSame('meta_test_token_12345', $setting->access_token);
        $this->assertNotSame('meta_test_token_12345', (string) $setting->getRawOriginal('access_token'));
        $this->assertSame(['user_name', 'policy_number', 'sales_user_name'], $setting->activation_body_parameters);
        $this->assertSame(['pin_token'], $setting->activation_button_parameters);
        $this->assertSame(['promoter_name', 'plan_name'], $setting->preregistration_body_parameters);
        $this->assertSame(['preregistration_token'], $setting->preregistration_button_parameters);
    }

    public function test_admin_can_send_template_test_message(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'phone_number_id' => '113206948334320',
            'access_token' => 'meta_test_token_12345',
            'activation_template_name' => 'activation_pin_template',
            'activation_language_code' => 'es_MX',
            'pin_reset_template_name' => 'reset_pin_template',
            'pin_reset_language_code' => 'es_MX',
            'preregistration_template_name' => 'policy_preregistration_template',
            'preregistration_language_code' => 'es_MX',
            'appointment_request_template_name' => 'appointment_request_template',
            'appointment_request_language_code' => 'es_MX',
            'appointment_completed_template_name' => 'appointment_completed_template',
            'appointment_completed_language_code' => 'es_MX',
            'default_language' => 'es_MX',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'contacts' => [['input' => '5213312345678', 'wa_id' => '5213312345678']],
                'messages' => [['id' => 'wamid.TEST123']],
            ], 200),
        ]);

        $this->actingAs($admin);

        Livewire::test(WhatsAppSettingsPage::class)
            ->set('testPhone', '5213312345678')
            ->set('testTemplateName', 'activation_pin_template')
            ->set('testLanguageCode', 'es_MX')
            ->set('testParameters', "Juan Perez\n1234")
            ->set('testButtonUrlParameters', 'abc123token')
            ->call('sendTestMessage')
            ->assertHasNoErrors()
            ->assertSet('lastTestMessageId', 'wamid.TEST123');

        Http::assertSent(function ($request) {
            $components = $request['template']['components'] ?? [];

            return str_contains($request->url(), '/v22.0/113206948334320/messages')
                && $request['template']['name'] === 'activation_pin_template'
                && $request['template']['language']['code'] === 'es_MX'
                && count($components) === 2
                && ($components[0]['type'] ?? null) === 'body'
                && ($components[0]['parameters'][0]['text'] ?? null) === 'Juan Perez'
                && ($components[0]['parameters'][1]['text'] ?? null) === '1234'
                && ($components[1]['type'] ?? null) === 'button'
                && ($components[1]['sub_type'] ?? null) === 'url'
                && ($components[1]['index'] ?? null) === '0'
                && ($components[1]['parameters'][0]['text'] ?? null) === 'abc123token';
        });
    }
}
