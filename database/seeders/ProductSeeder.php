<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * ProductSeeder — Seeds product lines/families (formerly categories).
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['id' => 'cat1', 'name' => 'Red Wine',    'icon' => '🍷', 'color' => '#8B1A1A'],
            ['id' => 'cat2', 'name' => 'White Wine',  'icon' => '🥂', 'color' => '#C8A951'],
            ['id' => 'cat3', 'name' => 'Tequila',     'icon' => '🥃', 'color' => '#D4A017'],
            ['id' => 'cat4', 'name' => 'Mezcal',      'icon' => '🌵', 'color' => '#6B8E23'],
            ['id' => 'cat5', 'name' => 'Whisky',      'icon' => '🥃', 'color' => '#8B4513'],
            ['id' => 'cat6', 'name' => 'Beer',        'icon' => '🍺', 'color' => '#DAA520'],
            ['id' => 'cat7', 'name' => 'Champagne',   'icon' => '🍾', 'color' => '#B8860B'],
            ['id' => 'cat8', 'name' => 'Liqueur',     'icon' => '🍸', 'color' => '#4B0082'],
        ];
        foreach ($products as $p) {
            Product::updateOrCreate(['id' => $p['id']], $p);
        }
    }
}
