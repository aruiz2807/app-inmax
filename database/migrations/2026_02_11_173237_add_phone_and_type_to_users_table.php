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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('profile', ['Admin', 'Doctor', 'Sales', 'User'])->default('User')->after('name');
            $table->timestamp('phone_verified_at')->after('email_verified_at')->nullable();
            $table->string('phone', 10)->after('email_verified_at')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
            $table->dropColumn('phone_verified_at');
            $table->dropColumn('profile');
        });
    }
};
