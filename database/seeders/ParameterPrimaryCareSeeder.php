<?php

namespace Database\Seeders;

use App\Models\Parameter;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ParameterPrimaryCareSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = Service::updateOrCreate(
            ['name' => 'Consulta medico general'],
            [
                'type' => 'Event',
            ]
        );

        Parameter::updateOrCreate(
            ['type' => 'MG', 'key' => 'Consulta'],
            [
                'description' => 'Consulta medico general',
                'value' => $service->id,
            ]
        );
    }
}
