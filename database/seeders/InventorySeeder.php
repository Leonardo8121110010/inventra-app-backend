<?php

namespace Database\Seeders;

use App\Models\Inventory;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $products = \App\Models\Product::pluck('id')->toArray();
        $branches = ['matriz', 'suc1', 'suc2', 'suc3'];

        foreach ($branches as $branchId) {
            foreach ($products as $productId) {
                Inventory::updateOrCreate(
                    ['branch_id' => $branchId, 'product_id' => $productId],
                    ['qty' => 1]
                );
            }
        }
    }
}
