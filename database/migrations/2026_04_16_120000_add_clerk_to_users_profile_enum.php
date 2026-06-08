<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE users MODIFY COLUMN profile ENUM('Admin', 'Doctor', 'Sales', 'Clerk', 'User') NOT NULL DEFAULT 'User'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("UPDATE users SET profile = 'User' WHERE profile = 'Clerk'");
        DB::statement("ALTER TABLE users MODIFY COLUMN profile ENUM('Admin', 'Doctor', 'Sales', 'User') NOT NULL DEFAULT 'User'");
    }
};
