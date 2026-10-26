<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalog = collect(config('permissions.catalog', []));
        $permissionsByCode = $catalog->mapWithKeys(function (array $attributes, string $code): array {
            $permission = Permission::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => $attributes['name'],
                    'group_name' => $attributes['group'] ?? null,
                    'description' => $attributes['description'] ?? null,
                    'is_active' => $attributes['is_active'] ?? true,
                ]
            );

            return [$code => $permission->id];
        });

        $adminCodes = $catalog
            ->filter(fn (array $attributes): bool => in_array('Admin', $attributes['default_profiles'] ?? [], true))
            ->keys()
            ->all();
        $permissionIds = $permissionsByCode->only($adminCodes)->values()->all();
        $nonAdminPermissionIds = $permissionsByCode->except($adminCodes)->values()->all();

        $systemAdmin = User::query()
            ->where('email', 'super@admin.com')
            ->orWhere('phone', '3310000000')
            ->first();

        if (! $systemAdmin) {
            return;
        }

        $systemAdmin->permissions()->syncWithoutDetaching($permissionIds);
        $systemAdmin->permissions()->detach($nonAdminPermissionIds);
    }
}
