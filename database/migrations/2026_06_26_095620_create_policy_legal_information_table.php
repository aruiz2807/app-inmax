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
            $table->foreignId('legal_relationship_id')->nullable()->constrained(
                table: 'relationships'
            );
            $table->string('cfdi_rfc', 13);
            $table->string('cfdi_name')->nullable();
            $table->string('cfdi_postal_code')->nullable();
            $table->foreignId('cfdi_regime_id')->nullable()->constrained(
                table: 'cfdi_regimes'
            );
            $table->foreignId('cfdi_use_id')->nullable()->constrained(
                table: 'cfdi_uses'
            );
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
