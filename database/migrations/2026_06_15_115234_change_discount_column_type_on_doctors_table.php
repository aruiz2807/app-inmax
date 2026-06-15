<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE doctors
            MODIFY COLUMN discount DECIMAL(8,2) NULL DEFAULT 0.00,
            MODIFY COLUMN commission DECIMAL(8,2) NULL DEFAULT 0.00;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE doctors
            MODIFY COLUMN discount TINYINT NULL,
            MODIFY COLUMN commission TINYINT NULL;
        ");
    }
};
