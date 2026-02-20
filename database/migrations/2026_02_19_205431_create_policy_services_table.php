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
        Schema::create('policy_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained(
                table: 'policies'
            );
            $table->foreignId('service_id')->constrained(
                table: 'services'
            );
            $table->tinyInteger('included');
            $table->tinyInteger('used')->default(0);
            $table->tinyInteger('extra')->default(0);
            $table->timestamps();

            $table->unique(['policy_id', 'service_id'], 'policy_service_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('policy_services');
    }
};
