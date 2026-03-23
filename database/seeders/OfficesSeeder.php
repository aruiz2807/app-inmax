<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OfficesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('offices')->insert([
            'name' => 'Centro del Riñón',
            'address' => 'Av. Plan de San Luis 1776, Lomas del Country',
            'maps_url' => 'https://maps.app.goo.gl/5TQTNEzeJ3FKCK6Z9',
        ]);

        DB::table('offices')->insert([
            'name' => 'Consultorio Av. Mexico',
            'address' => 'Av. Mexico 2446, Ladron de Guevara',
            'maps_url' => 'https://maps.app.goo.gl/jSBVhZ1nxYkcjCdg8',
        ]);
    }
}
