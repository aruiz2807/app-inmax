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
        Schema::create('appointment_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained(
                table: 'appointments'
            );
            $table->tinyText('symptoms')->nullable();
            $table->tinyText('findings')->nullable();
            $table->tinyText('diagnosis')->nullable();
            $table->tinyText('treatment')->nullable();
            $table->tinyText('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->timestamps();
        });
    }

    /**
    * Reverse the migrations.
    */
    public function down(): void
    {
        Schema::dropIfExists('appointment_notes');
    }
};
