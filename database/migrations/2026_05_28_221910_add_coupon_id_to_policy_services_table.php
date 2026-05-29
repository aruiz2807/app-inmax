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
        Schema::table('policy_services', function (Blueprint $table) {
            $table->foreignId('coupon_id')->after('service_id')->nullable()->constrained('coupons');
            $table->unique(['policy_id', 'coupon_id'], 'policy_services_coupon_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policy_services', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropUnique('policy_services_coupon_unique');
            $table->dropColumn('coupon_id');
        });
    }
};
