<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\Article;
use App\Models\Supplier;

/**
 * ArticleSeeder — Seeds individual sellable items/SKUs (formerly ProductsSeeder).
 */
class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Suppliers
        $supplierNames = [
            'FERNANDA/KOSOMLUMIL',
            'LLUVIA DE ESTRELLAS',
            'MORIN',
            'CASTELLO',
            'SANTILLAN'
        ];

        $suppliers = [];
        foreach ($supplierNames as $s) {
            $suppliers[$s] = Supplier::firstOrCreate(
                ['id' => Str::slug($s)],
                ['name' => $s]
            );
        }

        // 2. Create Products (product lines — stored in categories table)
        $prodTequila = Product::updateOrCreate(
            ['id' => 'cat-tequila'],
            ['name' => 'Tequila', 'icon' => 'Martini', 'color' => '#f59e0b', 'parent_id' => null]
        );

        // Miel is now a ROOT product, not a sub-product of Tequila
        $prodMiel = Product::updateOrCreate(
            ['id' => 'cat-miel'],
            ['name' => 'Miel', 'icon' => 'Amphora', 'color' => '#d97706', 'parent_id' => null]
        );

        // Subproducts
        $subs = [
            'BLANCO'     => Product::updateOrCreate(['id' => 'sub-blanco'],      ['name' => 'Blanco',           'icon' => 'Martini', 'parent_id' => $prodTequila->id]),
            'REPOSADO'   => Product::updateOrCreate(['id' => 'sub-reposado'],    ['name' => 'Reposado',         'icon' => 'Martini', 'parent_id' => $prodTequila->id]),
            'AÑEJO'      => Product::updateOrCreate(['id' => 'sub-anejo'],       ['name' => 'Añejo',            'icon' => 'Martini', 'parent_id' => $prodTequila->id]),
            'EXTRA'      => Product::updateOrCreate(['id' => 'sub-extra'],       ['name' => 'Extra Añejo',      'icon' => 'Martini', 'parent_id' => $prodTequila->id]),
            'BLANCO P'   => Product::updateOrCreate(['id' => 'sub-blanco-p'],    ['name' => 'Blanco Premium',   'icon' => 'Martini', 'parent_id' => $prodTequila->id]),
            'REPOSADO P' => Product::updateOrCreate(['id' => 'sub-reposado-p'],  ['name' => 'Reposado Premium', 'icon' => 'Martini', 'parent_id' => $prodTequila->id]),
            'AÑEJO P'    => Product::updateOrCreate(['id' => 'sub-anejo-p'],     ['name' => 'Añejo Premium',    'icon' => 'Martini', 'parent_id' => $prodTequila->id]),
            'EXTRA P'    => Product::updateOrCreate(['id' => 'sub-extra-p'],     ['name' => 'Extra Premium',    'icon' => 'Martini', 'parent_id' => $prodTequila->id]),
            'SABOR'      => Product::updateOrCreate(['id' => 'sub-sabor'],       ['name' => 'Sabor',            'icon' => 'Martini', 'parent_id' => $prodTequila->id]),
            'MIEL'       => Product::updateOrCreate(['id' => 'sub-miel'],        ['name' => 'Agave',            'icon' => 'Amphora', 'parent_id' => $prodMiel->id]),
            'PACHITA'    => Product::updateOrCreate(['id' => 'sub-pachita'],     ['name' => 'Pachita',          'icon' => 'Martini', 'parent_id' => $prodTequila->id]),
        ];

        // 3. Articles data: [name, supplier_key, product_key, cost, freight, price]
        $articlesData = [
            ['TEQUILA BLANCO',       'FERNANDA/KOSOMLUMIL', 'BLANCO',     172, 30, 1212],
            ['TEQUILA REPOSADO',     'FERNANDA/KOSOMLUMIL', 'REPOSADO',   224, 30, 1524],
            ['TEQUILA AÑEJO',        'FERNANDA/KOSOMLUMIL', 'AÑEJO',      328, 30, 2148],
            ['TEQUILA EXTRA AÑEJO',  'FERNANDA/KOSOMLUMIL', 'EXTRA',      420, 30, 2700],
            ['BLANCO PREMIUM',       'LLUVIA DE ESTRELLAS', 'BLANCO P',   350, 30, 1900],
            ['REPOSADO PREMIUM',     'LLUVIA DE ESTRELLAS', 'REPOSADO P', 380, 30, 2255],
            ['AÑEJO PREMIUM',        'MORIN',               'AÑEJO P',    460, 30, 2940],
            ['EXTRA PREMIUM',        'MORIN',               'EXTRA P',    580, 30, 3172],
            ['ALMENDRA',             'CASTELLO',            'SABOR',      280, 30, 1550],
            ['CAFÉ',                 'MORIN',               'SABOR',      230, 30, 1170],
            ['CHOCOLATE',            'CASTELLO',            'SABOR',      280, 30, 1550],
            ['CREMA IRLAN',          'MORIN',               'SABOR',      230, 30, 1170],
            ['GUANABANA',            'CASTELLO',            'SABOR',      280, 30, 1550],
            ['MARACUYA',             'CASTELLO',            'SABOR',      280, 30, 1550],
            ['MIEL AGAVE',           'FERNANDA/KOSOMLUMIL', 'MIEL',        45, 30, 712.50],
            ['CANELA',               'CASTELLO',            'SABOR',      280, 30, 1550],
            ['NUEZ',                 'MORIN',               'SABOR',      230, 30, 1170],
            ['MORA AZUL',            'LLUVIA DE ESTRELLAS', 'SABOR',      290, 30, 1600],
            ['COCO',                 'MORIN',               'SABOR',      230, 30, 1170],
            ['GRANADA',              'LLUVIA DE ESTRELLAS', 'SABOR',      290, 30, 1600],
            ['TAMARINDO',            'CASTELLO',            'SABOR',      280, 30, 1550],
            ['FRESA',                'CASTELLO',            'SABOR',      280, 30, 1550],
            ['GUAYABA',              'CASTELLO',            'SABOR',      280, 30, 1550],
            ['MANGO',                'CASTELLO',            'SABOR',      280, 30, 1550],
            ['PIÑA COLADA',          'CASTELLO',            'SABOR',      280, 30, 1550],
            ['JAMAICA',              'MORIN',               'SABOR',      230, 30, 1170],
            ['CACAO',                'MORIN',               'SABOR',      230, 30, 1170],
            ['ZARZAMORA',            'MORIN',               'SABOR',      230, 30, 1170],
            ['DURAZNO',              'MORIN',               'SABOR',      230, 30, 1170],
            ['CACAHUATE',            'MORIN',               'SABOR',      230, 30, 1170],
            ['FRAMBUESA',            'LLUVIA DE ESTRELLAS', 'SABOR',      290, 30, 1600],
            ['PACHITA COCO',         'LLUVIA DE ESTRELLAS', 'PACHITA',    174, 30, 816],
            ['PACHITA GRANADA',      'LLUVIA DE ESTRELLAS', 'PACHITA',    174, 30, 816],
            ['PACHITA MORA AZUL',    'LLUVIA DE ESTRELLAS', 'PACHITA',    174, 30, 816],
            ['PACHITA TAMARINDO',    'LLUVIA DE ESTRELLAS', 'PACHITA',    174, 30, 816],
            ['BLANCO PREMIUM',       'SANTILLAN',           'BLANCO P',   230, 20, 1375],
            ['REPOSADO PREMIUM',     'SANTILLAN',           'REPOSADO P', 250, 20, 1620],
            ['AÑEJO PREMIUM',        'SANTILLAN',           'AÑEJO P',    330, 20, 2222.50],
            ['EXTRA PREMIUM',        'SANTILLAN',           'EXTRA P',    380, 20, 2720],
            ['ALMENDRA',             'SANTILLAN',           'SABOR',      155, 20, 1137.50],
            ['CAFÉ',                 'SANTILLAN',           'SABOR',      155, 20, 1137.50],
            ['COCO',                 'SANTILLAN',           'SABOR',      155, 20, 1137.50],
            ['MANGO',                'SANTILLAN',           'SABOR',      155, 20, 1137.50],
        ];

        foreach ($articlesData as $index => $row) {
            $nameStr     = $row[0];
            $supplierStr = $row[1];
            $productStr  = $row[2];
            $cost        = $row[3];
            $freight     = $row[4];
            $price       = $row[5];

            $supplierObj = $suppliers[$supplierStr];
            $productObj  = $subs[$productStr];
            $sku         = strtoupper(substr(Str::slug($supplierStr), 0, 3)) . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT);

            Article::updateOrCreate(
                ['sku' => $sku],
                [
                    'id'          => Str::slug($nameStr . '-' . $supplierStr) . '-' . $index,
                    'name'        => $nameStr,
                    'supplier_id' => $supplierObj->id,
                    'category_id' => $productObj->id,
                    'size_ml'     => 750,
                    'cost'        => $cost,
                    'freight'     => $freight,
                    'total_cost'  => $cost + $freight,
                    'price'       => $price,
                    'min_stock'   => 5,
                    'active'      => true,
                ]
            );
        }
    }
}
