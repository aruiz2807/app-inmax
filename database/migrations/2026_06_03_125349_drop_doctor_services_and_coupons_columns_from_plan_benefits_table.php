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
        Schema::table('plan_benefits', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign('plan_benefits_doctor_coupon_id_foreign');
            $table->dropForeign('plan_benefits_doctor_service_id_foreign');

            // Drop indexes
            $table->dropUnique('plan_benefits_doctor_coupon_unique');
            $table->dropUnique('plan_benefits_doctor_service_unique');

            $table->dropIndex('plan_benefits_doctor_coupon_id_foreign');
            $table->dropIndex('plan_benefits_doctor_service_id_foreign');

            // Drop columns
            $table->dropColumn([
                'doctor_coupon_id',
                'doctor_service_id',
                'amount',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_benefits', function (Blueprint $table) {
            $table->foreignId('doctor_service_id')->nullable()->after('coupon_id')->constrained('doctor_services');
            $table->foreignId('doctor_coupon_id')->nullable()->after('coupon_id')->constrained('doctor_coupons');
            
            $table->unique(['plan_id', 'doctor_service_id'], 'plan_benefits_doctor_service_unique');
            $table->unique(['plan_id', 'doctor_coupon_id'], 'plan_benefits_doctor_coupon_unique');

            $table->decimal('amount', 8, 2)->after('events')->nullable();
        });
    }
};
