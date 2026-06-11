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
        Schema::create('medication_purchase_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('medication_purchase_id')
                ->constrained('medication_purchases')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('medication_id')
                ->constrained('medications')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->integer('requested_quantity')->default(0);
            $table->integer('received_quantity')->default(0);

            $table->decimal('price', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medication_purchase_details');
    }
};
