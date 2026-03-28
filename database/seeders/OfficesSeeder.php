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
        DB::table('offices')->updateOrInsert(
            ['name' => 'Inmax Country'], // unique key
            [
                'address' => 'Av. Plan de San Luis 1831, Col. San Bernardo',
                'maps_url' => 'https://maps.app.goo.gl/QmgmumzpiJKdupBu7',
            ]
        );

        DB::table('offices')->updateOrInsert(
            ['name' => 'Inmax Providencia'],
            [
                'address' => 'Calle José Enrique Rodo 2844, Prados Providencia',
                'maps_url' => 'https://maps.app.goo.gl/zkXeA5nJcCgDD52y6',
            ]
        );
    }
}
