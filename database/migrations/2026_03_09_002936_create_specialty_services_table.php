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
        Schema::create('specialty_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialty_id')->constrained(
                table: 'specialties'
            );
            $table->foreignId('service_id')->constrained(
                table: 'services'
            );
            $table->timestamps();

            $table->unique(['specialty_id', 'service_id'], 'specialty_service_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('specialty_services');
    }
};
