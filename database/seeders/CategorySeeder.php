<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['id' => 'cat1', 'name' => 'Vino Tinto',  'icon' => '🍷', 'color' => '#8B1A1A'],
            ['id' => 'cat2', 'name' => 'Vino Blanco', 'icon' => '🥂', 'color' => '#C8A951'],
            ['id' => 'cat3', 'name' => 'Tequila',     'icon' => '🥃', 'color' => '#D4A017'],
            ['id' => 'cat4', 'name' => 'Mezcal',      'icon' => '🌵', 'color' => '#6B8E23'],
            ['id' => 'cat5', 'name' => 'Whisky',      'icon' => '🥃', 'color' => '#8B4513'],
            ['id' => 'cat6', 'name' => 'Cerveza',     'icon' => '🍺', 'color' => '#DAA520'],
            ['id' => 'cat7', 'name' => 'Champagne',   'icon' => '🍾', 'color' => '#B8860B'],
            ['id' => 'cat8', 'name' => 'Licor',       'icon' => '🍸', 'color' => '#4B0082'],
        ];
        foreach ($categories as $c) {
            Category::updateOrCreate(['id' => $c['id']], $c);
        }
    }
}
