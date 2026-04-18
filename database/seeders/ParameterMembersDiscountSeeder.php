<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;

class ParameterMembersDiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Parameter::updateOrCreate(
            ['type' => 'DM', 'key' => 'Descuento'],
            [
                'description' => 'Porcentaje de descuento en medicamentos',
                'value' => '15',
            ]
        );
    }
}
