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
        Schema::create('appointment_prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained(
                table: 'appointments'
            );
            $table->foreignId('medication_id')->constrained(
                table: 'medications'
            );
            $table->tinyInteger('quantity');
            $table->string('dose', 50);
            $table->string('frequency', 50);
            $table->string('duration', 50);
            $table->enum('status', ['Prescribed', 'Dispensed', 'Cancelled'])->default('Prescribed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_prescriptions');
    }
};
