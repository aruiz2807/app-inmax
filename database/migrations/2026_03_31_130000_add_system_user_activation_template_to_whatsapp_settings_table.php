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
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->string('system_user_activation_template_name')->nullable()->after('activation_template_name');
            $table->string('system_user_activation_language_code', 10)->nullable()->after('system_user_activation_template_name');
            $table->json('system_user_activation_body_parameters')->nullable()->after('system_user_activation_language_code');
            $table->json('system_user_activation_button_parameters')->nullable()->after('system_user_activation_body_parameters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'system_user_activation_template_name',
                'system_user_activation_language_code',
                'system_user_activation_body_parameters',
                'system_user_activation_button_parameters',
            ]);
        });
    }
};
