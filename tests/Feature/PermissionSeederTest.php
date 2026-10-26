<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_admin_receives_only_default_admin_permissions(): void
    {
        $catalog = config('permissions.catalog', []);
        $expectedCodes = collect($catalog)
            ->filter(fn (array $attributes): bool => in_array('Admin', $attributes['default_profiles'] ?? [], true))
            ->keys()
            ->all();

        $admin = User::factory()->create(['email' => 'super@admin.com']);
        $nonAdminPermission = Permission::query()->create([
            'code' => 'view.clerk.medications',
            'name' => 'Ver medicamentos de mostrador',
            'is_active' => true,
        ]);
        $customPermission = Permission::query()->create([
            'code' => 'custom.permission',
            'name' => 'Permiso personalizado',
            'is_active' => true,
        ]);
        $admin->permissions()->attach([$nonAdminPermission->id, $customPermission->id]);

        $this->seed(PermissionSeeder::class);

        $this->assertEqualsCanonicalizing(
            [...$expectedCodes, 'custom.permission'],
            $admin->permissions()->pluck('code')->all()
        );
        $this->assertSame(count($catalog), Permission::query()->count());
    }
}
