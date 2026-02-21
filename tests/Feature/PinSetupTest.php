<?php

namespace Tests\Feature;

use App\Livewire\Auth\PinSetupPage;
use App\Models\LegalDocument;
use App\Models\User;
use App\Models\UserLegalAcceptance;
use App\Models\UserPinSetupToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PinSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_pin_setup_screen_can_be_rendered_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'pin' => null,
            'pin_set_at' => null,
        ]);

        $token = 'valid-setup-token';

        UserPinSetupToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(30),
        ]);

        $response = $this->get('/pin/setup/'.$token);

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee($user->phone);
    }

    public function test_user_can_define_a_pin_with_a_valid_token(): void
    {
        $user = User::factory()->create([
            'pin' => null,
            'pin_set_at' => null,
        ]);

        $terms = LegalDocument::query()->create([
            'type' => LegalDocument::TYPE_TERMS,
            'version' => 'v1.0',
            'title' => 'Terminos y condiciones',
            'content' => 'Contenido de terminos para pruebas de aceptacion.',
            'is_active' => true,
            'effective_at' => now()->subMinute(),
            'activated_at' => now()->subMinute(),
        ]);

        $privacy = LegalDocument::query()->create([
            'type' => LegalDocument::TYPE_PRIVACY,
            'version' => 'v1.0',
            'title' => 'Aviso de privacidad',
            'content' => 'Contenido de aviso de privacidad para pruebas de aceptacion.',
            'is_active' => true,
            'effective_at' => now()->subMinute(),
            'activated_at' => now()->subMinute(),
        ]);

        $token = 'setup-token-123';

        $pinToken = UserPinSetupToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(30),
        ]);

        Livewire::test(PinSetupPage::class, ['token' => $token])
            ->set('pin', '1234')
            ->set('pin_confirmation', '1234')
            ->set('acceptTerms', true)
            ->set('acceptPrivacy', true)
            ->set('acceptSensitiveData', true)
            ->call('save')
            ->assertRedirect(route('login', absolute: false));

        $user->refresh();
        $pinToken->refresh();

        $this->assertNotNull($user->pin_set_at);
        $this->assertTrue(Hash::check('1234', $user->pin));
        $this->assertNotNull($pinToken->used_at);

        $acceptance = UserLegalAcceptance::query()->first();

        $this->assertNotNull($acceptance);
        $this->assertSame($pinToken->id, $acceptance->user_pin_setup_token_id);
        $this->assertSame($terms->id, $acceptance->terms_document_id);
        $this->assertSame($privacy->id, $acceptance->privacy_document_id);
        $this->assertTrue($acceptance->accepted_terms);
        $this->assertTrue($acceptance->accepted_privacy);
        $this->assertTrue($acceptance->accepted_sensitive_data);
    }

    public function test_user_must_accept_legal_documents_to_define_pin(): void
    {
        $user = User::factory()->create([
            'pin' => null,
            'pin_set_at' => null,
        ]);

        LegalDocument::query()->create([
            'type' => LegalDocument::TYPE_TERMS,
            'version' => 'v1.0',
            'title' => 'Terminos y condiciones',
            'content' => 'Contenido de terminos para pruebas de aceptacion.',
            'is_active' => true,
            'effective_at' => now()->subMinute(),
            'activated_at' => now()->subMinute(),
        ]);

        LegalDocument::query()->create([
            'type' => LegalDocument::TYPE_PRIVACY,
            'version' => 'v1.0',
            'title' => 'Aviso de privacidad',
            'content' => 'Contenido de aviso de privacidad para pruebas de aceptacion.',
            'is_active' => true,
            'effective_at' => now()->subMinute(),
            'activated_at' => now()->subMinute(),
        ]);

        $token = 'setup-token-legal-required';

        UserPinSetupToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(30),
        ]);

        Livewire::test(PinSetupPage::class, ['token' => $token])
            ->set('pin', '1234')
            ->set('pin_confirmation', '1234')
            ->call('save')
            ->assertHasErrors([
                'acceptTerms',
                'acceptPrivacy',
                'acceptSensitiveData',
            ]);

        $user->refresh();

        $this->assertNull($user->pin_set_at);
        $this->assertDatabaseCount('user_legal_acceptances', 0);
    }

    public function test_pin_setup_screen_shows_expired_message_when_token_is_expired(): void
    {
        $user = User::factory()->create();

        $token = 'expired-token-123';

        UserPinSetupToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->subMinute(),
        ]);

        $response = $this->get('/pin/setup/'.$token);

        $response->assertStatus(200);
        $response->assertSee('Este enlace ya vencio. Solicita uno nuevo al administrador.');
    }

    public function test_pin_setup_screen_shows_used_message_when_token_was_used(): void
    {
        $user = User::factory()->create();

        $token = 'used-token-123';

        UserPinSetupToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(30),
            'used_at' => now(),
        ]);

        $response = $this->get('/pin/setup/'.$token);

        $response->assertStatus(200);
        $response->assertSee('Este enlace ya fue usado. Solicita uno nuevo al administrador.');
    }
}
