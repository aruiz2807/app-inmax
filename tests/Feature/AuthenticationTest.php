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
            'profile' => 'User',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->post('/login', [
            'phone' => $user->phone,
            'password' => '1234',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('user.home', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $this->post('/login', [
            'phone' => $user->phone,
            'password' => '6543',
        ]);

        $this->assertGuest();
    }

    public function test_admin_users_can_authenticate_using_phone_pin_login(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->post('/login', [
            'phone' => $admin->phone,
            'password' => '1234',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_doctor_users_redirect_to_doctor_home_after_login(): void
    {
        $doctor = User::factory()->create([
            'profile' => 'Doctor',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->post('/login', [
            'phone' => $doctor->phone,
            'password' => '1234',
        ]);

        $this->assertAuthenticatedAs($doctor);
        $response->assertRedirect(route('doctor.home', absolute: false));
    }
}
