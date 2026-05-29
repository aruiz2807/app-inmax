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
            $table->foreignId('coupon_id')->after('plan_id')->nullable()->constrained(
                table: 'coupons'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_benefits', function (Blueprint $table) {
            $table->dropForeign('plan_benefits_coupon_id_foreign');
            $table->dropColumn('coupon_id');
        });
    }
};
