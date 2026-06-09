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
        Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_hash')->unique();
            $table->string('meta_object')->nullable();
            $table->string('event_type')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->json('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_events');
    }
};
