<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;

class BackfillUserPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(PermissionSeeder::class);

        $permissionsByProfile = $this->permissionIdsByProfile();

        User::query()
            ->select(['id', 'profile'])
            ->doesntHave('permissions')
            ->chunkById(100, function ($users) use ($permissionsByProfile): void {
                foreach ($users as $user) {
                    $permissionIds = $permissionsByProfile[$user->profile] ?? [];

                    if ($permissionIds === []) {
                        continue;
                    }

                    $user->permissions()->syncWithoutDetaching($permissionIds);
                }
            });
    }

    /**
     * Resolve permission IDs for each default profile from the catalog.
     *
     * @return array<string, array<int, int>>
     */
    private function permissionIdsByProfile(): array
    {
        $profileCodes = [];

        foreach (config('permissions.catalog', []) as $code => $attributes) {
            foreach ($attributes['default_profiles'] ?? [] as $profile) {
                $profileCodes[$profile][] = $code;
            }
        }

        $allCodes = collect($profileCodes)->flatten()->unique()->values()->all();

        $permissions = Permission::query()
            ->whereIn('code', $allCodes)
            ->pluck('id', 'code');

        $permissionIdsByProfile = [];

        foreach ($profileCodes as $profile => $codes) {
            $permissionIdsByProfile[$profile] = collect($codes)
                ->map(fn (string $code): ?int => $permissions->get($code))
                ->filter()
                ->values()
                ->all();
        }

        return $permissionIdsByProfile;
    }
}
