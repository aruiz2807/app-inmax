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
        Schema::create('policy_legal_information', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained(
                table: 'policies'
            );
            $table->string('legal_name');
            $table->string('legal_address');
            $table->string('tax_rfc', 13);
            $table->string('tax_name');
            $table->string('tax_address');
            $table->string('tax_regime');
            $table->string('tax_use');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_legal_information');
    }
};
