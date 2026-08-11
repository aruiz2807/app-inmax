<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ParameterAmbulancesCommissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Parameter::updateOrCreate(
            ['type' => 'AMB', 'key' => 'Comision'],
            [
                'description' => 'Comision por ambulancias',
                'value' => 200,
            ]
        );
    }
}
