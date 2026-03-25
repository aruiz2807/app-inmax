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
            $table->string('preregistration_type', 30)
                ->default('individual_policy')
                ->after('parent_policy_id');

            $table->index(
                ['parent_policy_id', 'preregistration_type', 'used_at', 'cancelled_at', 'expires_at'],
                'policy_preregistrations_capacity_idx'
            );
        });

        DB::table('policy_preregistrations')
            ->whereIn('parent_policy_id', function ($query) {
                $query->select('id')
                    ->from('policies')
                    ->where('type', 'Group');
            })
            ->update([
                'preregistration_type' => 'group_member',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('policy_preregistrations', function (Blueprint $table) {
            $table->dropIndex('policy_preregistrations_capacity_idx');
            $table->dropColumn('preregistration_type');
        });
    }
};
