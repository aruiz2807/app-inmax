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
        Schema::table('medication_movements', function (Blueprint $table) {
            $table->foreignId('medication_purchase_id')
                ->nullable()
                ->after('prescription_id')
                ->constrained('medication_purchases')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->text('adjustment_comment')
                ->nullable()
                ->after('adjustment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('medication_movements', function (Blueprint $table) {
            $table->dropForeign(['medication_purchase_id']);

            $table->dropColumn([
                'medication_purchase_id',
                'adjustment_comment'
            ]);
        });
    }
};
