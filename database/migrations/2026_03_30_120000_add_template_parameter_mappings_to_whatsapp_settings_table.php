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
            $table->json('activation_body_parameters')->nullable()->after('activation_template_name');
            $table->json('activation_button_parameters')->nullable()->after('activation_body_parameters');
            $table->json('pin_reset_body_parameters')->nullable()->after('pin_reset_template_name');
            $table->json('pin_reset_button_parameters')->nullable()->after('pin_reset_body_parameters');
            $table->json('preregistration_body_parameters')->nullable()->after('preregistration_template_name');
            $table->json('preregistration_button_parameters')->nullable()->after('preregistration_body_parameters');
            $table->json('appointment_request_body_parameters')->nullable()->after('appointment_request_template_name');
            $table->json('appointment_request_button_parameters')->nullable()->after('appointment_request_body_parameters');
            $table->json('appointment_completed_body_parameters')->nullable()->after('appointment_completed_template_name');
            $table->json('appointment_completed_button_parameters')->nullable()->after('appointment_completed_body_parameters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'activation_body_parameters',
                'activation_button_parameters',
                'pin_reset_body_parameters',
                'pin_reset_button_parameters',
                'preregistration_body_parameters',
                'preregistration_button_parameters',
                'appointment_request_body_parameters',
                'appointment_request_button_parameters',
                'appointment_completed_body_parameters',
                'appointment_completed_button_parameters',
            ]);
        });
    }
};
