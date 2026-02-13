<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsAppSetting;
use App\Services\Auth\PinSetupTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PinSetupWhatsAppTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_activation_purpose_uses_activation_template_and_button_token(): void
    {
        $user = User::factory()->create([
            'name' => 'Juan Perez',
            'phone' => '3312345678',
        ]);

        WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'phone_number_id' => '113206948334320',
            'access_token' => 'meta_test_token_12345',
            'activation_template_name' => 'pin_activation_inmax',
            'pin_reset_template_name' => 'pin_reset_inmax',
            'default_language' => 'es_MX',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [['id' => 'wamid.ACTIVATION']],
            ], 200),
        ]);

        $service = app(PinSetupTokenService::class);
        $result = $service->generateSetupLink($user, null, PinSetupTokenService::PURPOSE_ACTIVATION);

        $this->assertTrue($result['whatsapp']['attempted']);
        $this->assertTrue($result['whatsapp']['ok']);

        $token = (string) last(explode('/', $result['url']));

        Http::assertSent(function ($request) use ($token) {
            return str_contains($request->url(), '/v22.0/113206948334320/messages')
                && $request['template']['name'] === 'pin_activation_inmax'
                && $request['template']['language']['code'] === 'es_MX'
                && $request['template']['components'][0]['parameters'][0]['text'] === 'Juan Perez'
                && $request['template']['components'][1]['sub_type'] === 'url'
                && $request['template']['components'][1]['parameters'][0]['text'] === $token;
        });
    }

    public function test_reset_purpose_uses_reset_template(): void
    {
        $user = User::factory()->create([
            'name' => 'Maria Lopez',
            'phone' => '3319998877',
        ]);

        WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'phone_number_id' => '113206948334320',
            'access_token' => 'meta_test_token_12345',
            'activation_template_name' => 'pin_activation_inmax',
            'pin_reset_template_name' => 'pin_reset_inmax',
            'default_language' => 'es_MX',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [['id' => 'wamid.RESET']],
            ], 200),
        ]);

        $service = app(PinSetupTokenService::class);
        $result = $service->generateSetupLink($user, null, PinSetupTokenService::PURPOSE_RESET);

        $this->assertTrue($result['whatsapp']['attempted']);
        $this->assertTrue($result['whatsapp']['ok']);

        Http::assertSent(function ($request) {
            return $request['template']['name'] === 'pin_reset_inmax';
        });
    }

    public function test_missing_whatsapp_configuration_falls_back_without_http_request(): void
    {
        $user = User::factory()->create([
            'phone' => '3312223344',
        ]);

        Http::fake();

        $service = app(PinSetupTokenService::class);
        $result = $service->generateSetupLink($user, null, PinSetupTokenService::PURPOSE_RESET);

        $this->assertFalse($result['whatsapp']['attempted']);
        $this->assertFalse($result['whatsapp']['ok']);
        $this->assertSame('missing_api_credentials', $result['whatsapp']['reason']);
        $this->assertStringContainsString('/pin/setup/', $result['url']);

        Http::assertNothingSent();
    }
}
