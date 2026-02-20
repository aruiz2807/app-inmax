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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(
                table: 'policies'
            );
            $table->foreignId('doctor_id')->constrained(
                table: 'doctors'
            );
            $table->date('date');
            $table->time('time');
            $table->boolean('covered');
            $table->enum('status', ['Booked', 'Cancelled', 'Completed', 'No-show'])->default('Booked');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
