<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Policy;
use App\Models\User;
use App\Models\WhatsAppSetting;
use App\Services\Auth\PinSetupTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PinSetupWhatsAppTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_user_activation_purpose_uses_dedicated_template_configuration(): void
    {
        $user = User::factory()->create([
            'name' => 'Miguel Ruiz',
            'phone' => '3318259507',
        ]);

        WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'phone_number_id' => '113206948334320',
            'access_token' => 'meta_test_token_12345',
            'system_user_activation_template_name' => 'system_user_pin_template',
            'system_user_activation_language_code' => 'es',
            'system_user_activation_body_parameters' => ['user_name', 'user_phone'],
            'system_user_activation_button_parameters' => ['pin_token'],
            'activation_template_name' => 'pin_activation_inmax',
            'activation_language_code' => 'en_US',
            'activation_body_parameters' => ['user_name', 'policy_number', 'start_date', 'sales_user_name'],
            'activation_button_parameters' => ['pin_token'],
            'pin_reset_template_name' => 'pin_reset_inmax',
            'pin_reset_language_code' => 'en_US',
            'default_language' => 'fr',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [['id' => 'wamid.SYSTEM_USER']],
            ], 200),
        ]);

        $service = app(PinSetupTokenService::class);
        $result = $service->generateSetupLink($user, null, PinSetupTokenService::PURPOSE_SYSTEM_USER_ACTIVATION);

        $this->assertTrue($result['whatsapp']['attempted']);
        $this->assertTrue($result['whatsapp']['ok']);

        $token = (string) last(explode('/', $result['url']));

        Http::assertSent(function ($request) use ($token) {
            return str_contains($request->url(), '/v22.0/113206948334320/messages')
                && $request['template']['name'] === 'system_user_pin_template'
                && $request['template']['language']['code'] === 'es'
                && $request['template']['components'][0]['parameters'][0]['text'] === 'Miguel Ruiz'
                && $request['template']['components'][0]['parameters'][1]['text'] === '3318259507'
                && $request['template']['components'][1]['sub_type'] === 'url'
                && $request['template']['components'][1]['parameters'][0]['text'] === $token;
        });
    }

    public function test_activation_purpose_uses_configured_template_parameters_and_button_token(): void
    {
        $salesUser = User::factory()->create([
            'profile' => 'Sales',
            'name' => 'Promotor Uno',
        ]);

        $user = User::factory()->create([
            'name' => 'Juan Perez',
            'phone' => '3312345678',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan Oro',
            'price' => 999.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        $policy = Policy::query()->create([
            'user_id' => $user->id,
            'sales_user_id' => $salesUser->id,
            'plan_id' => $plan->id,
            'number' => 'POL-3001',
            'type' => 'Individual',
            'start_date' => '2026-03-15',
            'status' => 'Active',
        ]);

        WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'phone_number_id' => '113206948334320',
            'access_token' => 'meta_test_token_12345',
            'activation_template_name' => 'pin_activation_inmax',
            'activation_language_code' => 'es',
            'activation_body_parameters' => ['user_name', 'policy_number', 'start_date', 'sales_user_name'],
            'activation_button_parameters' => ['pin_token'],
            'pin_reset_template_name' => 'pin_reset_inmax',
            'pin_reset_language_code' => 'en_US',
            'default_language' => 'fr',
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
                && $request['template']['language']['code'] === 'es'
                && $request['template']['components'][0]['parameters'][0]['text'] === 'Juan Perez'
                && $request['template']['components'][0]['parameters'][1]['text'] === 'POL-3001'
                && $request['template']['components'][0]['parameters'][2]['text'] === '15/03/2026'
                && $request['template']['components'][0]['parameters'][3]['text'] === 'Promotor Uno'
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
            'activation_language_code' => 'es_MX',
            'pin_reset_language_code' => 'en_US',
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
            return $request['template']['name'] === 'pin_reset_inmax'
                && $request['template']['language']['code'] === 'en_US';
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

    public function test_mexico_numbers_use_521_prefix_first(): void
    {
        $user = User::factory()->create([
            'name' => 'Carlos Ramos',
            'phone' => '3314445566',
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
                'messages' => [['id' => 'wamid.MX521']],
            ], 200),
        ]);

        $service = app(PinSetupTokenService::class);
        $result = $service->generateSetupLink($user, null, PinSetupTokenService::PURPOSE_ACTIVATION);

        $this->assertTrue($result['whatsapp']['ok']);
        $this->assertSame('5213314445566', $result['whatsapp']['to']);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['to'] === '5213314445566');
    }

    public function test_mexico_numbers_fall_back_to_52_when_521_fails(): void
    {
        $user = User::factory()->create([
            'name' => 'Alicia Gomez',
            'phone' => '3318887766',
        ]);

        WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'phone_number_id' => '113206948334320',
            'access_token' => 'meta_test_token_12345',
            'activation_template_name' => 'pin_activation_inmax',
            'pin_reset_template_name' => 'pin_reset_inmax',
            'default_language' => 'es_MX',
        ]);

        Http::fakeSequence()
            ->push([
                'error' => [
                    'message' => 'Invalid recipient',
                ],
            ], 400)
            ->push([
                'messages' => [['id' => 'wamid.MX52']],
            ], 200);

        $service = app(PinSetupTokenService::class);
        $result = $service->generateSetupLink($user, null, PinSetupTokenService::PURPOSE_RESET);

        $this->assertTrue($result['whatsapp']['ok']);
        $this->assertSame('523318887766', $result['whatsapp']['to']);
        Http::assertSentCount(2);
    }
}
