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
        Schema::create('user_legal_acceptances', function (Blueprint $table) {
            $table->id();
            $table->string('acceptance_code', 40)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_pin_setup_token_id')
                ->nullable()
                ->constrained('user_pin_setup_tokens')
                ->nullOnDelete();
            $table->foreignId('terms_document_id')->nullable()->constrained('legal_documents')->nullOnDelete();
            $table->foreignId('privacy_document_id')->nullable()->constrained('legal_documents')->nullOnDelete();
            $table->string('terms_version', 50);
            $table->string('privacy_version', 50);
            $table->boolean('accepted_terms');
            $table->boolean('accepted_privacy');
            $table->boolean('accepted_sensitive_data');
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->unique('user_pin_setup_token_id');
            $table->index(['user_id', 'accepted_at']);
            $table->index('accepted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_legal_acceptances');
    }
};
