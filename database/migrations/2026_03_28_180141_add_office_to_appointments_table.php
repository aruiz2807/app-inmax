<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            Schema::disableForeignKeyConstraints();
            DB::table('appointments')->truncate();
            Schema::enableForeignKeyConstraints();

            $table->dropForeign('appointments_doctor_id_foreign');
            $table->dropColumn('doctor_id');

            $table->foreignId('requested_by_user_id')->nullable()->after('user_id')->constrained(
                table: 'users'
            );

            $table->foreignId('office_id')->nullable()->after('user_id')->constrained(
                table: 'offices'
            );

            $table->foreignId('doctor_id')->nullable()->after('user_id')->constrained(
                table: 'doctors'
            );

            $table->dropColumn('covered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->boolean('covered')->after('time');

            $table->dropForeign('appointments_doctor_id_foreign');
            $table->dropColumn('doctor_id');

            $table->dropForeign('appointments_office_id_foreign');
            $table->dropColumn('office_id');

            $table->dropForeign('appointments_requested_by_user_id_foreign');
            $table->dropColumn('requested_by_user_id');

            $table->foreignId('doctor_id')->after('user_id')->constrained(
                table: 'doctors'
            );
        });
    }
};
