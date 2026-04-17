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
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->string('code', 6);
            $table->string('name', 100);
            $table->string('trade_name', 100);
            $table->string('active_substance', 100);
            $table->string('lab', 50);
            $table->string('packaging', 100);
            $table->decimal('price_public', total: 8, places: 2);
            $table->decimal('price_members', total: 8, places: 2);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};
