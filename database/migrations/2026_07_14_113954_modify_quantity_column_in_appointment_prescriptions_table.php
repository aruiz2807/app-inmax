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
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE appointment_prescriptions MODIFY COLUMN quantity VARCHAR(20) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE appointment_prescriptions ALTER COLUMN quantity TYPE VARCHAR(20)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('ALTER TABLE appointment_prescriptions MODIFY COLUMN quantity INTEGER(11) NOT NULL');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE appointment_prescriptions ALTER COLUMN quantity TYPE INTEGER(11)');
        }
    }
};
