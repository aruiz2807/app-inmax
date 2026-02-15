<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $admin = User::query()
            ->where('email', 'super@admin.com')
            ->orWhere('phone', '3310000000')
            ->first();

        if (! $admin) {
            $admin = new User();
        }

        $admin->forceFill([
            'name' => 'Admin',
            'profile' => 'Admin',
            'email' => 'super@admin.com',
            'email_verified_at' => now(),
            'phone' => '3310000000',
            'phone_verified_at' => now(),
            'pin' => Hash::make('1234'),
            'pin_set_at' => now(),
            'password' => Hash::make('ld19M7sY3FzE'),
        ])->save();
    }
}
