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
        Schema::create('whatsapp_message_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_message_id')->constrained('whatsapp_messages')->cascadeOnDelete();
            $table->string('status', 30);
            $table->timestamp('meta_occurred_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_message_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_statuses');
    }
};
