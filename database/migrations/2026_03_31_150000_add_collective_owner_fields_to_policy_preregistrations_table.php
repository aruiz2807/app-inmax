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
            $table->foreignId('plan_id')->nullable()->change();
            $table->string('company_name', 100)->nullable();
            $table->string('company_type', 10)->nullable();
            $table->string('company_legal_name', 100)->nullable();
            $table->string('company_rfc', 13)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policy_preregistrations', function (Blueprint $table) {
            $table->dropColumn([
                'company_name',
                'company_type',
                'company_legal_name',
                'company_rfc',
            ]);
            $table->foreignId('plan_id')->nullable(false)->change();
        });
    }
};
