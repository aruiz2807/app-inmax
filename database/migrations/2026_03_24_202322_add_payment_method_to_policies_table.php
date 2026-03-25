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
        Schema::table('policies', function (Blueprint $table) {
            $table->string('payment_file_name')->nullable()->after('insurance');
            $table->string('payment_file_path')->nullable()->after('insurance');
            $table->string('payment_reference', 50)->nullable()->after('insurance');
            $table->enum('payment_method', ['CC', 'DC', 'TR'])->nullable()->after('insurance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policies', function (Blueprint $table) {
            $table->dropColumn('payment_method');
            $table->dropColumn('payment_reference');
            $table->dropColumn('payment_file_path');
            $table->dropColumn('payment_file_name');
        });
    }
};
