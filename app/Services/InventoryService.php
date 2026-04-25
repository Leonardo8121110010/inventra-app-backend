<?php

namespace App\Services;

use App\Models\Inventory;
use Illuminate\Support\Collection;

class InventoryService
{
    /**
     * Get all inventory as nested object: { branch_id: { product_id: qty } }
     */
    public function getAll(): array
    {
        $rows = Inventory::all();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->branch_id][$row->product_id] = $row->qty;
        }
        return $result;
    }

    /**
     * Get inventory for a specific branch.
     */
    public function getBranch(string $branchId): array
    {
        $rows = Inventory::where('branch_id', $branchId)->get();
        $result = [];
        foreach ($rows as $row) {
            $result[$row->product_id] = $row->qty;
        }
        return $result;
    }

    /**
     * Adjust stock for a branch-product pair.
     */
    public function adjust(string $branchId, string $productId, int $qty): Inventory
    {
        return Inventory::updateOrCreate(
            ['branch_id' => $branchId, 'product_id' => $productId],
            ['qty' => $qty]
        );
    }
}
