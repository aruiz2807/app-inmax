<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Models\WhatsAppSetting;
use App\Services\Auth\PolicyPreregistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PolicyPreregistrationWhatsAppTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_preregistration_template_sends_promoter_name_in_body_and_token_in_button(): void
    {
        $salesUser = User::factory()->create([
            'profile' => 'Sales',
        ]);

        $plan = Plan::query()->create([
            'name' => 'Plan Individual',
            'price' => 999.00,
            'type' => 'Individual',
            'status' => 'Active',
        ]);

        WhatsAppSetting::query()->create([
            'api_version' => 'v22.0',
            'phone_number_id' => '113206948334320',
            'access_token' => 'meta_test_token_12345',
            'activation_template_name' => 'pin_activation_inmax',
            'pin_reset_template_name' => 'pin_reset_inmax',
            'preregistration_template_name' => 'policy_preregistration_template',
            'default_language' => 'es_MX',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [['id' => 'wamid.PREREG']],
            ], 200),
        ]);

        $service = app(PolicyPreregistrationService::class);
        $result = $service->createInvitation(
            $salesUser,
            '3312345678',
            $plan->id
        );

        $this->assertTrue($result['whatsapp']['attempted']);
        $this->assertTrue($result['whatsapp']['ok']);

        $token = (string) last(explode('/', $result['url']));

        Http::assertSent(function ($request) use ($salesUser, $token) {
            $components = $request['template']['components'] ?? [];

            return str_contains($request->url(), '/v22.0/113206948334320/messages')
                && $request['template']['name'] === 'policy_preregistration_template'
                && $request['template']['language']['code'] === 'es_MX'
                && count($components) === 2
                && ($components[0]['type'] ?? null) === 'body'
                && ($components[0]['parameters'][0]['text'] ?? null) === $salesUser->name
                && ($components[1]['type'] ?? null) === 'button'
                && ($components[1]['sub_type'] ?? null) === 'url'
                && ($components[1]['index'] ?? null) === '0'
                && ($components[1]['parameters'][0]['text'] ?? null) === $token;
        });
    }
}
