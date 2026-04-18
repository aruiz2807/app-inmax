<?php

namespace Database\Seeders;

use App\Models\Parameter;
use Illuminate\Database\Seeder;

class ParameterCouponValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Parameter::updateOrCreate(
            ['type' => 'CP', 'key' => 'Valor'],
            [
                'description' => 'Importe cupon de medicamentos',
                'value' => '800',
            ]
        );
    }
}
