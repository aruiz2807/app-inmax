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
        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('phone')->nullable();
            $table->string('normalized_phone')->unique();
            $table->string('wa_id')->nullable()->unique();
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_contacts');
    }
};
