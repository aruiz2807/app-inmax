<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RelationshipsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $familyTies = [
            ['name' => 'Padre'],
            ['name' => 'Madre'],
            ['name' => 'Hijo(a)'],
            ['name' => 'Hermano(a)'],
            ['name' => 'Abuelo(a)'],
            ['name' => 'Nieto(a)'],
            ['name' => 'Bisabuelo(a)'],
            ['name' => 'Bisnieto(a)'],
            ['name' => 'Tío(a)'],
            ['name' => 'Sobrino(a)'],
            ['name' => 'Primo(a)'],
            ['name' => 'Esposo(a)'],
            ['name' => 'Cónyuge'],
            ['name' => 'Pareja'],
            ['name' => 'Suegro(a)'],
            ['name' => 'Yerno'],
            ['name' => 'Nuera'],
            ['name' => 'Cuñado(a)'],
            ['name' => 'Padrastro'],
            ['name' => 'Madrastra'],
            ['name' => 'Hijastro(a)'],
            ['name' => 'Tutor(a)'],
            ['name' => 'Otro'],
        ];

        foreach ($familyTies as $familyTie) {
            DB::table('relationships')->updateOrInsert(
                ['name' => $familyTie['name']],
                $familyTie
            );
        }
    }
}
