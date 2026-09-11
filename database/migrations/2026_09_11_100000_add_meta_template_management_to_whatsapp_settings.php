<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->string('business_account_id', 80)->nullable()->after('phone_number_id');
            $table->string('meta_app_id', 80)->nullable()->after('business_account_id');
        });

        Schema::create('whatsapp_message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('meta_id', 120)->unique();
            $table->string('name', 512);
            $table->string('language_code', 20);
            $table->string('status', 40)->index();
            $table->string('category', 40)->nullable();
            $table->json('components')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['name', 'language_code']);
            $table->index(['status', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_templates');

        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn(['business_account_id', 'meta_app_id']);
        });
    }
};
