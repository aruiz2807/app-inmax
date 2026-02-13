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
        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('api_version')->default('v22.0');
            $table->string('phone_number_id')->nullable();
            $table->text('access_token')->nullable();
            $table->string('activation_template_name')->nullable();
            $table->string('pin_reset_template_name')->nullable();
            $table->string('default_language', 10)->default('es_MX');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
    }
};
