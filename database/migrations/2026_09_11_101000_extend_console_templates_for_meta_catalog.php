<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_console_templates', function (Blueprint $table) {
            $table->foreignId('whatsapp_message_template_id')
                ->nullable()
                ->after('id')
                ->constrained('whatsapp_message_templates')
                ->nullOnDelete();
            $table->boolean('allow_console')->default(true)->after('is_active');
            $table->boolean('allow_marketing')->default(true)->after('allow_console');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_console_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('whatsapp_message_template_id');
            $table->dropColumn(['allow_console', 'allow_marketing']);
        });
    }
};
