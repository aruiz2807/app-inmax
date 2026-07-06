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
        $parameters = [
            [
                'type' => 'RS',
                'key' => 'Facebook',
                'description' => 'Facebook',
                'value' => 'https://www.facebook.com/share/18j2wXAju6/?mibextid=wwXIfr',
            ],
            [
                'type' => 'RS',
                'key' => 'Instagram',
                'description' => 'Instagram',
                'value' => 'https://www.instagram.com/inmaxplataforma/',
            ],
            [
                'type' => 'RS',
                'key' => 'Phone',
                'description' => 'Teléfono',
                'value' => '+ 52 33 1366 6626',
            ],
            [
                'type' => 'RS',
                'key' => 'Email',
                'description' => 'Correo',
                'value' => 'contacto@inmax.mx',
            ],
            [
                'type' => 'RS',
                'key' => 'Whatsapp',
                'description' => 'Whatsapp',
                'value' => '+ 52 33 1366 6626',
            ],
            [
                'type' => 'RS',
                'key' => 'Tiktok',
                'description' => 'Tiktok',
                'value' => 'https://www.tiktok.com/@inmaxplataforma',
            ],
            [
                'type' => 'RS',
                'key' => 'Maps',
                'description' => 'Ubicación',
                'value' => 'https://maps.app.goo.gl/4u4jt7dhaLhGw3qS7?g_st=iw',
            ],
            [
                'type' => 'RS',
                'key' => 'Page',
                'description' => 'Página INMAX',
                'value' => 'https://inmax.mx/',
            ],
        ];

        foreach ($parameters as $parameter) {
            Parameter::updateOrCreate(
                [
                    'type' => $parameter['type'],
                    'key' => $parameter['key'],
                ],
                [
                    'description' => $parameter['description'],
                    'value' => $parameter['value'],
                ]
            );
        }
    }
}