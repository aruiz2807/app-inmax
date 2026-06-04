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
         DB::statement("
            ALTER TABLE doctors
            MODIFY COLUMN type
            ENUM('Doctor', 'Provider')
            NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         DB::statement("
            ALTER TABLE doctors
            MODIFY COLUMN type
            ENUM('Doctor', 'Lab', 'Hospital', 'Provider')
            NOT NULL
        ");
    }
};
