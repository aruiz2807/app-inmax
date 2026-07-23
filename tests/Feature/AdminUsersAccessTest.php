<?php

namespace Tests\Feature;

use App\Models\Permission;
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
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $permission = Permission::query()->create([
            'code' => 'view.admin.users',
            'name' => 'Ver usuarios',
            'group_name' => 'Administracion',
            'description' => 'Permite acceder a la administracion de usuarios.',
            'is_active' => true,
        ]);

        $admin->permissions()->attach($permission->id);

        $response = $this->actingAs($admin)->get(route('users'));

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_access_users_page(): void
    {
        $user = User::factory()->create([
            'profile' => 'User',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('users'));

        $response->assertStatus(403);
    }

    public function test_farmacia_can_access_appointment_when_it_has_permission(): void
    {
        $user = User::factory()->create([
            'profile' => 'Farmacia',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $permission = Permission::query()->create([
            'code' => 'view.admin.appointments',
            'name' => 'Ver citas',
            'group_name' => 'Administracion',
            'description' => 'Permite acceder al modulo administrativo de citas.',
            'is_active' => true,
        ]);

        $user->permissions()->attach($permission->id);

        $response = $this->actingAs($user)->get(route('appointments'));

        $response->assertStatus(200);
    }

    public function test_farmacia_cannot_access_users_even_with_permission(): void
    {
        $user = User::factory()->create([
            'profile' => 'Farmacia',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $permission = Permission::query()->create([
            'code' => 'view.admin.users',
            'name' => 'Ver usuarios',
            'group_name' => 'Administracion',
            'description' => 'Permite acceder a la administracion de usuarios.',
            'is_active' => true,
        ]);

        $user->permissions()->attach($permission->id);

        $response = $this->actingAs($user)->get(route('users'));

        $response->assertStatus(403);
    }

    public function test_farmacia_cannot_access_configuration_even_with_permission(): void
    {
        $user = User::factory()->create([
            'profile' => 'Farmacia',
            'pin' => '1234',
            'pin_set_at' => now(),
        ]);

        $permission = Permission::query()->create([
            'code' => 'view.settings.whatsapp',
            'name' => 'Ver configuracion de WhatsApp',
            'group_name' => 'Configuraciones',
            'description' => 'Permite acceder a la configuracion de WhatsApp.',
            'is_active' => true,
        ]);

        $user->permissions()->attach($permission->id);

        $response = $this->actingAs($user)->get(route('settings.whatsapp'));

        $response->assertStatus(403);
    }
}
