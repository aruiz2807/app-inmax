<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AmbulanceServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Service::updateOrCreate(
            ['name' => 'TRASLADO PROGRAMADO SENCILLO'],
            [
                'price' => 900.00,
                'type' => 'Event',
            ]
        );

        Service::updateOrCreate(
            ['name' => 'TRASLADO PROGRAMADO REDONDO'],
            [
                'price' => 1500.00,
                'type' => 'Event',
            ]
        );

        Service::updateOrCreate(
            ['name' => 'TRASLADO DE URGENCIA'],
            [
                'price' => 2200.00,
                'type' => 'Event',
            ]
        );

        Service::updateOrCreate(
            ['name' => 'TRASLADO CUIDADOS AVANZADOS'],
            [
                'price' => 2900.00,
                'type' => 'Event',
            ]
        );
    }
}
