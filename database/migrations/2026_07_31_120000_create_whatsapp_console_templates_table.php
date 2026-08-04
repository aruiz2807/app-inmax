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
        Schema::create('whatsapp_console_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('meta_template_name');
            $table->string('language_code', 20)->default('es_MX');
            $table->text('example_text')->nullable();
            $table->json('body_variables')->nullable();
            $table->json('button_variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['meta_template_name', 'language_code'], 'wa_console_template_meta_language_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_console_templates');
    }
};
