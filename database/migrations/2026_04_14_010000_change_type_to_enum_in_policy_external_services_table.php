<?php

use App\Enums\ExternalServicesType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
        DB::statement('ALTER TABLE policy_external_services MODIFY type VARCHAR(255) NOT NULL');
    }
};
