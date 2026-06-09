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
            $table->string('webhook_verify_token')->nullable()->after('access_token');
            $table->text('app_secret')->nullable()->after('webhook_verify_token');
            $table->boolean('webhook_enabled')->default(false)->after('app_secret');
            $table->timestamp('webhook_last_received_at')->nullable()->after('webhook_enabled');
            $table->string('webhook_last_status')->nullable()->after('webhook_last_received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'webhook_verify_token',
                'app_secret',
                'webhook_enabled',
                'webhook_last_received_at',
                'webhook_last_status',
            ]);
        });
    }
};
