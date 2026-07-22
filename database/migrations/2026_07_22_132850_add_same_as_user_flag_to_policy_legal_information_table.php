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
        Schema::table('policy_legal_information', function (Blueprint $table) {
            $table->boolean('same_as_user')->default(false)->after('policy_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policy_legal_information', function (Blueprint $table) {
            $table->dropColumn('same_as_user');
        });
    }
};
