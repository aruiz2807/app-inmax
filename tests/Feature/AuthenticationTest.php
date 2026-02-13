<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'pin' => '123456',
            'pin_set_at' => now(),
        ]);

        $response = $this->post('/login', [
            'phone' => $user->phone,
            'password' => '123456',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'pin' => '123456',
            'pin_set_at' => now(),
        ]);

        $this->post('/login', [
            'phone' => $user->phone,
            'password' => '654321',
        ]);

        $this->assertGuest();
    }
}
