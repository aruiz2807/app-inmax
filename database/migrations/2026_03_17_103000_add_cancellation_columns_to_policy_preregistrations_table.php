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
        Schema::table('policy_preregistrations', function (Blueprint $table) {
            $table->foreignId('cancelled_by')
                ->nullable()
                ->after('used_at')
                ->constrained('users');
            $table->timestamp('cancelled_at')
                ->nullable()
                ->after('cancelled_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policy_preregistrations', function (Blueprint $table) {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancelled_by', 'cancelled_at']);
        });
    }
};
