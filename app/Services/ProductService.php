<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    /**
     * Get all products (product lines/families).
     */
    public function getAll(): Collection
    {
        return Product::all();
    }

    /**
     * Create a new product line.
     */
    public function create(array $data): Product
    {
        return Product::create($data);
    }

    /**
     * Update an existing product line.
     */
    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product;
    }

    /**
     * Delete a product line.
     */
    public function delete(Product $product): void
    {
        $product->delete();
    }
}
