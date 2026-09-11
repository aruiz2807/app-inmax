<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_marketing_campaign_recipients', function (Blueprint $table) {
            $table->string('response_type', 40)->nullable()->after('failed_at');
            $table->text('response_text')->nullable()->after('response_type');
            $table->json('response_payload')->nullable()->after('response_text');
            $table->timestamp('responded_at')->nullable()->after('response_payload');

            $table->index(['whatsapp_marketing_campaign_id', 'responded_at'], 'wa_mkt_recipient_response_idx');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_marketing_campaign_recipients', function (Blueprint $table) {
            $table->dropIndex('wa_mkt_recipient_response_idx');
            $table->dropColumn([
                'response_type',
                'response_text',
                'response_payload',
                'responded_at',
            ]);
        });
    }
};
