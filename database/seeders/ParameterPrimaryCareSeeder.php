<?php

namespace Database\Seeders;

use App\Models\Parameter;
use App\Models\Service;
use App\Models\Specialty;
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

        $specialty = Specialty::updateOrCreate(
            ['name' => 'Medico general'],
            [
                'service_id' => $service->id,
            ]
        );

        Parameter::updateOrCreate(
            ['type' => 'MG', 'key' => 'Consulta'],
            [
                'description' => 'Consulta medico general',
                'value' => $service->id,
            ]
        );

        Parameter::updateOrCreate(
            ['type' => 'MG', 'key' => 'Especialidad'],
            [
                'description' => 'Especialidad medico general',
                'value' => $specialty->id,
            ]
        );
    }
}
