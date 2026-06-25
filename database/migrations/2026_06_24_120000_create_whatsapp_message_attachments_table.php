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
        Schema::create('whatsapp_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_message_id')->constrained('whatsapp_messages')->cascadeOnDelete();
            $table->string('provider_media_id')->nullable()->index();
            $table->string('type', 50);
            $table->string('mime_type')->nullable();
            $table->string('file_name')->nullable();
            $table->text('caption')->nullable();
            $table->string('sha256')->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();
            $table->string('storage_disk', 50)->nullable();
            $table->string('storage_path')->nullable();
            $table->string('download_status', 30)->default('pending')->index();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamp('last_download_attempt_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'download_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_attachments');
    }
};
