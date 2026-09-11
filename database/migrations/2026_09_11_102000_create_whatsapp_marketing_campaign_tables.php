<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_marketing_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whatsapp_console_template_id');
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 40)->default('draft')->index();
            $table->json('body_mappings')->nullable();
            $table->json('button_mappings')->nullable();
            $table->string('header_media_type', 30)->nullable();
            $table->string('header_media_source', 30)->default('none');
            $table->text('header_media_url')->nullable();
            $table->string('header_media_disk', 50)->nullable();
            $table->string('header_media_path')->nullable();
            $table->string('header_media_name')->nullable();
            $table->string('header_media_mime')->nullable();
            $table->string('source_file_disk', 50)->nullable();
            $table->string('source_file_path')->nullable();
            $table->string('source_file_name')->nullable();
            $table->unsignedInteger('total_recipients')->default(0);
            $table->unsignedInteger('valid_recipients')->default(0);
            $table->unsignedInteger('invalid_recipients')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('whatsapp_console_template_id', 'wa_mkt_campaign_template_fk')
                ->references('id')
                ->on('whatsapp_console_templates')
                ->cascadeOnDelete();
            $table->foreign('created_by_user_id', 'wa_mkt_campaign_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::create('whatsapp_marketing_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whatsapp_marketing_campaign_id');
            $table->unsignedInteger('row_number');
            $table->string('customer_name')->nullable();
            $table->string('phone_raw', 60)->nullable();
            $table->string('phone_normalized', 60)->nullable()->index();
            $table->json('row_data')->nullable();
            $table->json('body_values')->nullable();
            $table->json('button_values')->nullable();
            $table->string('status', 40)->default('pending')->index();
            $table->string('wamid')->nullable()->index();
            $table->text('error_message')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['whatsapp_marketing_campaign_id', 'status'], 'wa_mkt_recipients_campaign_status_idx');
            $table->foreign('whatsapp_marketing_campaign_id', 'wa_mkt_recipient_campaign_fk')
                ->references('id')
                ->on('whatsapp_marketing_campaigns')
                ->cascadeOnDelete();
        });

        Schema::create('whatsapp_marketing_campaign_delivery_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('whatsapp_marketing_campaign_id');
            $table->unsignedBigInteger('whatsapp_marketing_campaign_recipient_id')->nullable();
            $table->unsignedBigInteger('sent_by_user_id')->nullable();
            $table->string('status', 40)->index();
            $table->string('recipient_phone', 60)->nullable()->index();
            $table->json('template_snapshot')->nullable();
            $table->json('request_payload')->nullable();
            $table->longText('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('whatsapp_marketing_campaign_id', 'wa_mkt_log_campaign_fk')
                ->references('id')
                ->on('whatsapp_marketing_campaigns')
                ->cascadeOnDelete();
            $table->foreign('whatsapp_marketing_campaign_recipient_id', 'wa_mkt_log_recipient_fk')
                ->references('id')
                ->on('whatsapp_marketing_campaign_recipients')
                ->nullOnDelete();
            $table->foreign('sent_by_user_id', 'wa_mkt_log_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_marketing_campaign_delivery_logs');
        Schema::dropIfExists('whatsapp_marketing_campaign_recipients');
        Schema::dropIfExists('whatsapp_marketing_campaigns');
    }
};
