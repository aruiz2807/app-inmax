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
        // Delete records where coupon_id is NULL
        DB::table('policy_services')
            ->whereNull('coupon_id')
            ->whereNull('service_id')
            ->delete();

        Schema::table('policy_services', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign('policy_services_doctor_service_id_foreign');
            $table->dropForeign('policy_services_doctor_coupon_id_foreign');

            // Drop indexes
            $table->dropUnique('policy_services_doctor_service_unique');
            $table->dropUnique('policy_services_doctor_coupon_unique');

            $table->dropIndex('policy_services_doctor_service_id_foreign');
            $table->dropIndex('policy_services_doctor_coupon_id_foreign');

            // Drop columns
            $table->dropColumn([
                'doctor_coupon_id',
                'doctor_service_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policy_services', function (Blueprint $table) {
            $table->foreignId('doctor_service_id')->nullable()->after('coupon_id')->constrained('doctor_services');
            $table->foreignId('doctor_coupon_id')->nullable()->after('coupon_id')->constrained('doctor_coupons');
            
            $table->unique(['policy_id', 'doctor_service_id'], 'policy_services_doctor_service_unique');
            $table->unique(['policy_id', 'doctor_coupon_id'], 'policy_services_doctor_coupon_unique');
        });
    }
};
