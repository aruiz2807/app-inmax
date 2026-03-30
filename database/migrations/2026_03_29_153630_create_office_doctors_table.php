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
        Schema::create('office_doctors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained(
                table: 'offices'
            );
            $table->foreignId('doctor_id')->constrained(
                table: 'doctors'
            );
            $table->timestamps();
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->dropForeign('doctors_office_id_foreign');
            $table->dropColumn('office_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->foreignId('office_id')->nullable()->after('specialty_id')->constrained(
                table: 'offices'
            );
        });

        Schema::dropIfExists('office_doctors');
    }
};
