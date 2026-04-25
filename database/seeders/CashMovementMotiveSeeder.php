<?php

namespace Database\Seeders;

use App\Models\CashMovementMotive;
use Illuminate\Database\Seeder;

class CashMovementMotiveSeeder extends Seeder
{
    public function run(): void
    {
        $motives = [
            ['id' => 'fondo_inicial',    'name' => 'Fondo de caja',             'type' => 'in',   'icon' => 'Vault',        'applies_commission' => false, 'sort_order' => 1],
            ['id' => 'visita_externa',   'name' => 'Visita de agente exterior',  'type' => 'in',   'icon' => 'UserCheck',    'applies_commission' => false, 'sort_order' => 2],
            ['id' => 'ingreso_extra',    'name' => 'Ingreso no registrado',       'type' => 'in',   'icon' => 'PlusCircle',   'applies_commission' => false, 'sort_order' => 3],
            ['id' => 'comision_agente',  'name' => 'Comisión pagada a agente',   'type' => 'out',  'icon' => 'Handshake',    'applies_commission' => true,  'sort_order' => 4],
            ['id' => 'gasto_operativo',  'name' => 'Gasto operativo',            'type' => 'out',  'icon' => 'Receipt',      'applies_commission' => false, 'sort_order' => 5],
            ['id' => 'deposito_banco',   'name' => 'Depósito bancario',          'type' => 'out',  'icon' => 'Landmark',     'applies_commission' => false, 'sort_order' => 6],
            ['id' => 'prestamo',         'name' => 'Préstamo / Anticipo',        'type' => 'out',  'icon' => 'HandCoins',    'applies_commission' => false, 'sort_order' => 7],
            ['id' => 'otro',             'name' => 'Otro',                       'type' => 'both', 'icon' => 'MoreHorizontal','applies_commission' => false, 'sort_order' => 8],
        ];

        foreach ($motives as $motive) {
            CashMovementMotive::updateOrCreate(['id' => $motive['id']], $motive);
        }
    }
}
