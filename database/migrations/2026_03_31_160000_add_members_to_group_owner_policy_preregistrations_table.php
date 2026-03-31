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
            $table->unsignedTinyInteger('members')->nullable()->after('company_rfc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policy_preregistrations', function (Blueprint $table) {
            $table->dropColumn('members');
        });
    }
};
