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
            ->set('pinResetTemplateName', 'reset_pin_template')
            ->set('defaultLanguage', 'es_MX')
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('whatsapp_settings', [
            'api_version' => 'v22.0',
            'phone_number_id' => '113206948334320',
            'activation_template_name' => 'activation_pin_template',
            'pin_reset_template_name' => 'reset_pin_template',
            'default_language' => 'es_MX',
        ]);

        $setting = WhatsAppSetting::query()->firstOrFail();

        $this->assertSame('meta_test_token_12345', $setting->access_token);
        $this->assertNotSame('meta_test_token_12345', (string) $setting->getRawOriginal('access_token'));
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
            'pin_reset_template_name' => 'reset_pin_template',
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
            ->set('testParameters', "Juan Perez\n1234\nhttps://app-inmax.test/pin/setup/abc")
            ->call('sendTestMessage')
            ->assertHasNoErrors()
            ->assertSet('lastTestMessageId', 'wamid.TEST123');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/v22.0/113206948334320/messages')
                && $request['template']['name'] === 'activation_pin_template'
                && $request['template']['language']['code'] === 'es_MX'
                && $request['template']['components'][0]['parameters'][0]['text'] === 'Juan Perez';
        });
    }
}
