<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Parameter;

class SocialMediaParamsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Parameter::insertOrIgnore([
            [
                'id' => 1,
                'type' => 'RS',
                'key' => 'Instagram',
                'description' => 'Instagram',
                'value' => 'https://www.instagram.com/sureinmax?igsh=MWVrcHBlNmZ5YzFweg==',
            ],
            [
                'id' => 2,
                'type' => 'RS',
                'key' => 'Phone',
                'description' => 'Teléfono',
                'value' => '+ 52 33 1366 6626',
            ],
            [
                'id' => 3,
                'type' => 'RS',
                'key' => 'Email',
                'description' => 'Correo',
                'value' => 'contacto@inmax-sure.com',
            ],
            [
                'id' => 4,
                'type' => 'RS',
                'key' => 'Whatsapp',
                'description' => 'Whatsapp',
                'value' => '+ 52 33 1366 6626',
            ],
            [
                'id' => 5,
                'type' => 'RS',
                'key' => 'Tiktok',
                'description' => 'Tiktok',
                'value' => 'https://www.tiktok.com/@inmaxsure',
            ],
            [
                'id' => 6,
                'type' => 'RS',
                'key' => 'Maps',
                'description' => 'Ubicación',
                'value' => 'https://maps.app.goo.gl/4u4jt7dhaLhGw3qS7?g_st=iw',
            ],
        ]);
    }
}