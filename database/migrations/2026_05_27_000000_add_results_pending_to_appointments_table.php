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

        DB::statement("\n            ALTER TABLE appointments\n            MODIFY status ENUM('Requested','Rejected','Booked','Cancelled','ResultsPending','Completed','No-show')\n            DEFAULT 'Requested'\n        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("\n            ALTER TABLE appointments\n            MODIFY status ENUM('Requested','Rejected','Booked','Cancelled','Completed','No-show')\n            DEFAULT 'Requested'\n        ");
    }
};