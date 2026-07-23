<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('policy_preregistrations', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->index(['phone', 'used_at'], 'policy_preregistrations_phone_used_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $duplicatePhones = DB::table('policy_preregistrations')
            ->select('phone')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone');

        if ($duplicatePhones->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede restaurar el indice unico de policy_preregistrations.phone porque existen teléfonos duplicados: '
                .$duplicatePhones->implode(', ')
            );
        }

        Schema::table('policy_preregistrations', function (Blueprint $table) {
            $table->dropIndex('policy_preregistrations_phone_used_idx');
            $table->unique('phone');
        });
    }
};
