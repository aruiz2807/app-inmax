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
            $table->unique(['plan_id', 'coupon_id'], 'plan_benefits_coupon_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_benefits', function (Blueprint $table) {
            $table->dropUnique('plan_benefits_coupon_unique');
        });
    }
};
