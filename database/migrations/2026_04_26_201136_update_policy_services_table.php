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
        Schema::dropIfExists('policy_services');

        // Re-create it with the new schema
        Schema::create('policy_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies');
            
            // New columns replacing service_id
            $table->foreignId('service_id')->nullable()->constrained('services');
            $table->foreignId('doctor_service_id')->nullable()->constrained('doctor_services');
            $table->foreignId('doctor_coupon_id')->nullable()->constrained('doctor_coupons');
            
            $table->tinyInteger('included');
            $table->tinyInteger('used')->default(0);
            $table->tinyInteger('extra')->default(0);
            $table->timestamps();

            // New unique constraints
            $table->unique(['policy_id', 'service_id'], 'policy_services_service_unique');
            $table->unique(['policy_id', 'doctor_service_id'], 'policy_services_doctor_service_unique');
            $table->unique(['policy_id', 'doctor_coupon_id'], 'policy_services_doctor_coupon_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_services');

        Schema::create('policy_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained('policies');
            $table->foreignId('service_id')->constrained('services');
            $table->tinyInteger('included');
            $table->tinyInteger('used')->default(0);
            $table->tinyInteger('extra')->default(0);
            $table->timestamps();

            $table->unique(['policy_id', 'service_id'], 'policy_service_unique');
        });
    }
};
