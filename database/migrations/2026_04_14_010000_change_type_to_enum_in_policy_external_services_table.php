<?php

use App\Enums\ExternalServicesType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        $values = implode(",", array_map(
            fn ($value) => "'{$value}'",
            array_column(ExternalServicesType::cases(), 'value')
        ));

        DB::statement("ALTER TABLE policy_external_services MODIFY type ENUM({$values}) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE policy_external_services MODIFY type VARCHAR(255) NOT NULL');
    }
};
