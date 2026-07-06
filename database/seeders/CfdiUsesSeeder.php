<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CfdiUsesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uses = [
            ['code' => 'G01', 'name' => 'Adquisición de mercancías'],
            ['code' => 'G02', 'name' => 'Devoluciones, descuentos o bonificaciones'],
            ['code' => 'G03', 'name' => 'Gastos en general'],
            ['code' => 'I01', 'name' => 'Construcciones'],
            ['code' => 'I02', 'name' => 'Mobiliario y equipo de oficina por inversiones'],
            ['code' => 'I03', 'name' => 'Equipo de transporte'],
            ['code' => 'I04', 'name' => 'Equipo de cómputo y accesorios'],
            ['code' => 'I05', 'name' => 'Dados, troqueles, moldes, matrices y herramental'],
            ['code' => 'I06', 'name' => 'Comunicaciones telefónicas'],
            ['code' => 'I07', 'name' => 'Comunicaciones satelitales'],
            ['code' => 'I08', 'name' => 'Otra maquinaria y equipo'],
            ['code' => 'D01', 'name' => 'Honorarios médicos, dentales y gastos hospitalarios'],
            ['code' => 'D02', 'name' => 'Gastos médicos por incapacidad o discapacidad'],
            ['code' => 'D03', 'name' => 'Gastos funerales'],
            ['code' => 'D04', 'name' => 'Donativos'],
            ['code' => 'D05', 'name' => 'Intereses reales efectivamente pagados por créditos hipotecarios (casa habitación)'],
            ['code' => 'D06', 'name' => 'Aportaciones voluntarias al SAR'],
            ['code' => 'D07', 'name' => 'Primas por seguros de gastos médicos'],
            ['code' => 'D08', 'name' => 'Gastos de transportación escolar obligatoria'],
            ['code' => 'D09', 'name' => 'Depósitos en cuentas para el ahorro, primas que tengan como base planes de pensiones'],
            ['code' => 'D10', 'name' => 'Pagos por servicios educativos (colegiaturas)'],
            ['code' => 'S01', 'name' => 'Sin efectos fiscales'],
            ['code' => 'CP01', 'name' => 'Pagos'],
            ['code' => 'CN01', 'name' => 'Nómina'],
        ];

        foreach ($uses as $use) {
            DB::table('cfdi_uses')->updateOrInsert(
                ['code' => $use['code']],
                ['name' => $use['name']]
            );
        }
    }
}
