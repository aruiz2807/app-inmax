<?php

namespace Tests\Feature;

use App\Livewire\Auth\PinSetupPage;
use App\Models\User;
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

        $token = 'setup-token-123';

        $pinToken = UserPinSetupToken::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(30),
        ]);

        Livewire::test(PinSetupPage::class, ['token' => $token])
            ->set('pin', '1234')
            ->set('pin_confirmation', '1234')
            ->call('save')
            ->assertRedirect(route('login', absolute: false));

        $user->refresh();
        $pinToken->refresh();

        $this->assertNotNull($user->pin_set_at);
        $this->assertTrue(Hash::check('1234', $user->pin));
        $this->assertNotNull($pinToken->used_at);
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
