<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsersAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_users_page(): void
    {
        $admin = User::factory()->create([
            'profile' => 'Admin',
            'pin' => '123456',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/users');

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_users_page(): void
    {
        $user = User::factory()->create([
            'profile' => 'User',
            'pin' => '123456',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/users');

        $response->assertStatus(403);
    }
}
