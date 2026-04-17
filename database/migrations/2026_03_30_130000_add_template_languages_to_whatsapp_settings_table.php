<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->string('activation_language_code', 10)->nullable()->after('activation_template_name');
            $table->string('pin_reset_language_code', 10)->nullable()->after('pin_reset_template_name');
            $table->string('preregistration_language_code', 10)->nullable()->after('preregistration_template_name');
            $table->string('appointment_request_language_code', 10)->nullable()->after('appointment_request_template_name');
            $table->string('appointment_completed_language_code', 10)->nullable()->after('appointment_completed_template_name');
        });

        DB::table('whatsapp_settings')
            ->whereNull('activation_language_code')
            ->update([
                'activation_language_code' => DB::raw('default_language'),
                'pin_reset_language_code' => DB::raw('default_language'),
                'preregistration_language_code' => DB::raw('default_language'),
                'appointment_request_language_code' => DB::raw('default_language'),
                'appointment_completed_language_code' => DB::raw('default_language'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'activation_language_code',
                'pin_reset_language_code',
                'preregistration_language_code',
                'appointment_request_language_code',
                'appointment_completed_language_code',
            ]);
        });
    }
};
