<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN profile ENUM('Admin', 'Doctor', 'Sales', 'Clerk', 'Receptionist', 'User', 'Dispatcher') NOT NULL DEFAULT 'User'");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement("UPDATE users SET profile = 'User' WHERE profile = 'Dispatcher'");    
        DB::statement("ALTER TABLE users MODIFY COLUMN profile ENUM('Admin', 'Doctor', 'Sales', 'Clerk', 'Receptionist', 'User') NOT NULL DEFAULT 'User'");
    }
};
