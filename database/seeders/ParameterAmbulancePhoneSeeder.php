<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;

class ParameterAmbulancePhoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Parameter::updateOrCreate(
            ['type' => 'AMB', 'key' => 'Phone'],
            [
                'description' => 'Numero para solicitar servicio de ambulancia',
                'value' => '3313666626',
            ]
        );
    }
}
