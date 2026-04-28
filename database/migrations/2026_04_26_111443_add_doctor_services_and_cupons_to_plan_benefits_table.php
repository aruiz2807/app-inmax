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
        // Drop the table entirely if it exists
        Schema::dropIfExists('plan_benefits');

        // Re-create it with the new schema
        Schema::create('plan_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans');
            
            // New columns
            $table->foreignId('doctor_service_id')->nullable()->constrained('doctor_services');
            $table->foreignId('doctor_coupon_id')->nullable()->constrained('doctor_coupons');
            
            $table->tinyInteger('events')->nullable();
            $table->decimal('amount', 8, 2)->nullable();
            $table->timestamps();

            // New unique constraints
            $table->unique(['plan_id', 'doctor_service_id'], 'plan_benefits_doctor_service_unique');
            $table->unique(['plan_id', 'doctor_coupon_id'], 'plan_benefits_doctor_coupon_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_benefits');

        Schema::create('plan_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained(
                table: 'plans'
            );
            $table->foreignId('service_id')->constrained(
                table: 'services'
            );
            $table->tinyInteger('events')->nullable();
            $table->decimal('amount', total: 8, places: 2)->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'service_id'], 'benefits_plan_service_unique');
        });
    }
};
