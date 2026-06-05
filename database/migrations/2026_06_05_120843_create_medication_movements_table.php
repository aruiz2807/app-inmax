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
        Schema::create('medication_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['IN', 'OUT'])->default('IN');
            $table->boolean('adjustment')->default(false);
            $table->integer('quantity')->default(0);
            $table->text('reference')->nullable();
            $table->foreignId('prescription_id')->nullable()->constrained(
                table: 'appointment_prescriptions'
            )->nullOnDelete();
            //$table->foreignId('medication_purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_movements');
    }
};
