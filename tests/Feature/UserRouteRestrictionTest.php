<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRouteRestrictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_gets_forbidden_on_dashboard_route(): void
    {
        $user = User::factory()->create([
            'profile' => 'User',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(403);
    }

    public function test_user_profile_gets_forbidden_on_admin_prefix_route(): void
    {
        $user = User::factory()->create([
            'profile' => 'User',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('policies'));

        $response->assertStatus(403);
    }

    public function test_doctor_profile_gets_forbidden_on_dashboard_route(): void
    {
        $doctor = User::factory()->create([
            'profile' => 'Doctor',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($doctor)->get(route('dashboard'));

        $response->assertStatus(403);
    }

    public function test_doctor_profile_gets_forbidden_on_admin_prefix_route(): void
    {
        $doctor = User::factory()->create([
            'profile' => 'Doctor',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($doctor)->get(route('policies'));

        $response->assertStatus(403);
    }

    public function test_doctor_profile_gets_forbidden_on_user_prefix_route(): void
    {
        $doctor = User::factory()->create([
            'profile' => 'Doctor',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($doctor)->get(route('user.home'));

        $response->assertStatus(403);
    }

    public function test_doctor_profile_can_access_doctor_prefix_route(): void
    {
        $doctor = User::factory()->create([
            'profile' => 'Doctor',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($doctor)->get(route('doctor.home'));

        $response->assertStatus(200);
    }
}
