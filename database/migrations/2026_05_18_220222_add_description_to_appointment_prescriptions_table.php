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
    public function up(): void
    {
        // Make medication_id nullable
        DB::statement("
            ALTER TABLE appointment_prescriptions
            MODIFY medication_id BIGINT UNSIGNED NULL
        ");

        // Add description column
        Schema::table('appointment_prescriptions', function (Blueprint $table) {
            $table->string('description', 250)
                ->nullable()
                ->after('medication_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove description column
        Schema::table('appointment_prescriptions', function (Blueprint $table) {
            $table->dropColumn('description');
        });

        // Revert medication_id to NOT NULL
        DB::statement("
            ALTER TABLE appointment_prescriptions
            MODIFY medication_id BIGINT UNSIGNED NOT NULL
        ");
    }
};
