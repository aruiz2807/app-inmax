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
        Schema::create('plan_benefits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained(
                table: 'plans'
            );
            $table->foreignId('service_id')->constrained(
                table: 'services'
            );
            $table->tinyInteger('events')->nullable();
            $table->decimal('amount', total: 8, places: 2)->nullable();
            $table->timestamps();

            $table->unique(['plan_id', 'service_id'], 'benefits_plan_service_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plan_benefits', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropForeign(['service_id']);

            $table->dropUnique('benefits_plan_service_unique');
        });

        Schema::dropIfExists('plan_benefits');
    }
};
