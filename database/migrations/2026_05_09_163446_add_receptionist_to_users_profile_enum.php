<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN profile ENUM('Admin', 'Doctor', 'Sales', 'Clerk', 'Receptionist', 'User') NOT NULL DEFAULT 'User'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE users SET profile = 'User' WHERE profile = 'Receptionist'");
        DB::statement("ALTER TABLE users MODIFY COLUMN profile ENUM('Admin', 'Doctor', 'Sales', 'Clerk', 'User') NOT NULL DEFAULT 'User'");
    }
};
