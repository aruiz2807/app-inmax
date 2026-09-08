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
        Schema::table('appointment_services', function (Blueprint $table) {
            $table->decimal('subtotal', 8, 2)->nullable()->after('status');
            $table->decimal('coupon_discount', 8, 2)->nullable()->after('subtotal');
            $table->decimal('user_payment', 8, 2)->nullable()->after('coupon_discount');
            $table->decimal('commission', 8, 2)->nullable()->after('user_payment');
            $table->decimal('total', 8, 2)->nullable()->after('commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_services', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'coupon_discount', 'user_payment', 'commission', 'total']);
        });
    }
};
