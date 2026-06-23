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
        $permissionIds = collect(config('permissions.catalog', []))
            ->map(function (array $attributes, string $code): int {
                $permission = Permission::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $attributes['name'],
                        'group_name' => $attributes['group'] ?? null,
                        'description' => $attributes['description'] ?? null,
                        'is_active' => $attributes['is_active'] ?? true,
                    ]
                );

                return $permission->id;
            })
            ->values()
            ->all();

        $systemAdmin = User::query()
            ->where('email', 'super@admin.com')
            ->orWhere('phone', '3310000000')
            ->first();

        if (! $systemAdmin || $permissionIds === []) {
            return;
        }

        $systemAdmin->permissions()->syncWithoutDetaching($permissionIds);
    }
}
