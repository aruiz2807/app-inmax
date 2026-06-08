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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_conversation_id')->constrained('whatsapp_conversations')->cascadeOnDelete();
            $table->string('meta_message_id')->nullable()->unique();
            $table->string('direction', 20);
            $table->string('type', 50)->default('text');
            $table->string('status', 30)->default('queued');
            $table->string('from_phone')->nullable();
            $table->string('to_phone')->nullable();
            $table->string('template_name')->nullable();
            $table->string('template_language_code', 20)->nullable();
            $table->text('body_text')->nullable();
            $table->string('meta_conversation_id')->nullable();
            $table->string('meta_pricing_category')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_conversation_id', 'created_at']);
            $table->index(['direction', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
