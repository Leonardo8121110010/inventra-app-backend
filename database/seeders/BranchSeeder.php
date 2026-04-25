<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        // Remove old/legacy branch IDs if they exist
        \App\Models\Branch::whereIn('id', ['suc1', 'suc2', 'suc3', 'matriz', 'chulas', 'cedral', 'moneybar'])->delete();

        $branches = [
            ['id' => '00-castillo', 'name' => 'Castillo',        'type' => 'matriz',   'address' => ''],
            ['id' => '10-chulas',   'name' => 'Chulas',          'type' => 'sucursal', 'address' => ''],
            ['id' => '20-cedral',   'name' => 'El Cedral',       'type' => 'sucursal', 'address' => ''],
            ['id' => '30-moneybar', 'name' => 'Money Bar',       'type' => 'sucursal', 'address' => ''],
        ];
        foreach ($branches as $b) {
            Branch::updateOrCreate(['id' => $b['id']], $b);
        }
    }
}
