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

        User::query()->updateOrCreate(
            ['email' => 'super@admin.com'],
            [
                'name' => 'Admin',
                'profile' => 'Admin',
                'phone' => '3310000000',
                'phone_verified_at' => now(),
                'pin' => Hash::make('1234'),
                'pin_set_at' => now(),
                'password' => Hash::make('ld19M7sY3FzE'),
                'email_verified_at' => now(),
            ]
        );
    }
}
