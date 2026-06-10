<?php

namespace Database\Seeders;

use App\Models\Parameter;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ParameterDoctorDesktopVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Parameter::updateOrCreate(
            ['type' => 'SITE', 'key' => 'Doctor_VersionDesktop'],
             [
                'description' => 'Habilitar versión de escritorio para doctores',
                'value' => 'Inactiva',
            ]
        );
    }
}
