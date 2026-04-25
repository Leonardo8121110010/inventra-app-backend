<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;

class SupplierService
{
    /**
     * Get all active suppliers.
     */
    public function getAll(): Collection
    {
        return Supplier::where('active', true)->get();
    }

    /**
     * Find a supplier by ID.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findById(string $id): Supplier
    {
        return Supplier::findOrFail($id);
    }

    /**
     * Create a new supplier.
     */
    public function create(array $data): Supplier
    {
        $data['active'] = $data['active'] ?? true;

        return Supplier::create($data);
    }

    /**
     * Update an existing supplier.
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);

        return $supplier->refresh();
    }

    /**
     * Soft-deactivate a supplier (toggle active flag).
     */
    public function deactivate(Supplier $supplier): Supplier
    {
        $supplier->update(['active' => false]);

        return $supplier->refresh();
    }
}
