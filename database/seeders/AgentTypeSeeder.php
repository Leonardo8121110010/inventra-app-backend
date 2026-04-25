<?php

namespace Database\Seeders;

use App\Models\AgentType;
use Illuminate\Database\Seeder;

class AgentTypeSeeder extends Seeder
{
    public function run(): void
    {
        AgentType::truncate();

        $types = [
            ['id' => 'guia_interno',     'name' => 'Guía Interno',     'icon' => 'Users'],
            ['id' => 'guia_externo',     'name' => 'Guía Externo',     'icon' => 'Users'],
            ['id' => 'taxista_interno',  'name' => 'Taxista Interno',  'icon' => 'Car'],
            ['id' => 'taxista_externo',  'name' => 'Taxista Externo',  'icon' => 'Car'],
            ['id' => 'vendedor',         'name' => 'Vendedor',         'icon' => 'User'],
        ];

        foreach ($types as $type) {
            AgentType::create($type);
        }
    }
}
