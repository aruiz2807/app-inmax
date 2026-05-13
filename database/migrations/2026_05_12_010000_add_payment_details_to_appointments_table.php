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
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('payment_method', 10)->nullable()->after('total');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->string('payment_attachment_path')->nullable()->after('payment_reference');
            $table->string('payment_attachment_name')->nullable()->after('payment_attachment_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_reference',
                'payment_attachment_path',
                'payment_attachment_name',
            ]);
        });
    }
};
