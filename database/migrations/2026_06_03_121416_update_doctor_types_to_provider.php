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
        DB::table('doctors')
            ->whereIn('type', ['Lab', 'Hospital'])
            ->update([
                'type' => 'Provider'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No es posible saber cuáles eran Lab y cuáles Hospital, por lo que no se puede revertir esta migración de manera segura.
    }
};
