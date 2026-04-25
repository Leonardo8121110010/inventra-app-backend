<?php

namespace Database\Seeders;

use App\Models\CommissionRule;
use Illuminate\Database\Seeder;

class CommissionRuleSeeder extends Seeder
{
    public function run(): void
    {
        // Eliminar reglas anteriores para evitar duplicados si se corre varias veces (opcional si es db:seed)
        CommissionRule::truncate();

        $ruleCounter = 1;

        // Comisiones por Llegada (Visit)
        $arrivalRules = [
            ['agent_type_id' => 'guia_externo', 'value' => 500],
            ['agent_type_id' => 'guia_interno', 'value' => 200],
            ['agent_type_id' => 'taxista_externo', 'value' => 500],
            ['agent_type_id' => 'taxista_interno', 'value' => 250],
        ];

        foreach ($arrivalRules as $ar) {
            CommissionRule::create([
                'id' => 'cr' . $ruleCounter++,
                'agent_type_id' => $ar['agent_type_id'],
                'commission_type' => 'fixed',
                'value' => $ar['value'],
                'trigger' => 'visit',
                'active' => true
            ]);
        }

        // Bono x 7 botellas ($500 por cada 7 botellas vendidas a la semana)
        $volumeTypes = ['guia_interno', 'guia_externo', 'taxista_interno', 'taxista_externo'];
        foreach ($volumeTypes as $vt) {
            CommissionRule::create([
                'id' => 'cr' . $ruleCounter++,
                'agent_type_id' => $vt,
                'commission_type' => 'fixed',
                'value' => 500,
                'trigger' => 'volume',
                'volume_threshold' => 7,
                'period' => 'weekly',
                'active' => true
            ]);
        }

        // Recuperar Productos para Comisiones por Venta
        $products = \App\Models\Article::with(['product', 'supplier'])->get();

        foreach ($products as $p) {
            $nameStr = strtoupper($p->name);
            $catStr = strtoupper($p->product->name ?? '');
            $supplier = strtoupper($p->supplier->name ?? '');

            $vend = 0;
            $int = 0;
            $ext = 0;

            // Determinar precios según la imagen y requerimientos
            if (str_contains($catStr, 'SABOR') || str_contains($catStr, 'PACHITA') || str_contains($catStr, 'MIEL')) {
                // Si es Santillan, ignorar por ahora (no hay info de comisiones)
                if (str_contains($supplier, 'SANTILLAN')) {
                    $vend = 0; $int = 0; $ext = 0;
                }
                elseif (str_contains($nameStr, 'MIEL')) {
                    $vend = 68;
                    $int = 0;
                    $ext = 0;
                }
                else {
                    $vend = 150;
                    $int = 150;
                    $ext = 150;
                }
            }
            else {
                if (str_contains($supplier, 'FERNANDA') || str_contains($supplier, 'KOSOMLUMIL')) {
                    // Precios base para Fernanda/Kosomlumil
                    if (str_contains($nameStr, 'BLANCO')) {
                        $vend = 200;
                        $int = 150;
                        $ext = 150;
                    }
                    elseif (str_contains($nameStr, 'REPOSADO')) {
                        $vend = 250;
                        $int = 150;
                        $ext = 150;
                    }
                    elseif (str_contains($nameStr, 'EXTRA')) {
                        $vend = 350;
                        $int = 300;
                        $ext = 300;
                    }
                    elseif (str_contains($nameStr, 'AÑEJO')) {
                        $vend = 300;
                        $int = 200;
                        $ext = 200;
                    }
                }
                elseif (str_contains($supplier, 'LLUVIA DE ESTRELLAS')) {
                    if (str_contains($nameStr, 'BLANCO') || str_contains($nameStr, 'REPOSADO')) {
                        $vend = 350;
                        $int = 300;
                        $ext = 350;
                    }
                }
                elseif (str_contains($supplier, 'MORIN')) {
                    if (str_contains($nameStr, 'EXTRA')) {
                        $vend = 450;
                        $int = 400;
                        $ext = 350;
                    }
                    elseif (str_contains($nameStr, 'AÑEJO')) {
                        $vend = 400;
                        $int = 350;
                        $ext = 350;
                    }
                }
            }

            // Insertar reglas por producto solo si tienen comisión > 0
            if ($vend > 0) {
                CommissionRule::create([
                    'id' => 'cr' . $ruleCounter++, 'agent_type_id' => 'vendedor',
                    'product_id' => $p->id, 'commission_type' => 'fixed', 'value' => $vend, 'trigger' => 'sale', 'active' => true
                ]);
            }
            if ($int > 0) {
                CommissionRule::create([
                    'id' => 'cr' . $ruleCounter++, 'agent_type_id' => 'guia_interno',
                    'product_id' => $p->id, 'commission_type' => 'fixed', 'value' => $int, 'trigger' => 'sale', 'active' => true
                ]);
                CommissionRule::create([
                    'id' => 'cr' . $ruleCounter++, 'agent_type_id' => 'taxista_interno',
                    'product_id' => $p->id, 'commission_type' => 'fixed', 'value' => $int, 'trigger' => 'sale', 'active' => true
                ]);
            }
            if ($ext > 0) {
                CommissionRule::create([
                    'id' => 'cr' . $ruleCounter++, 'agent_type_id' => 'guia_externo',
                    'product_id' => $p->id, 'commission_type' => 'fixed', 'value' => $ext, 'trigger' => 'sale', 'active' => true
                ]);
                CommissionRule::create([
                    'id' => 'cr' . $ruleCounter++, 'agent_type_id' => 'taxista_externo',
                    'product_id' => $p->id, 'commission_type' => 'fixed', 'value' => $ext, 'trigger' => 'sale', 'active' => true
                ]);
            }
        }
    }
}