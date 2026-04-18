<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;

class ParameterDiscountMedicationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Parameter::updateOrCreate(
            ['type' => 'CP', 'key' => 'Medicamentos'],
            [
                'description' => 'Cupon descuento de medicamentos',
                'value' => '0',
            ]
        );
    }
}
