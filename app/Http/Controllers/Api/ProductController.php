<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    protected ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Get all product lines/families.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->productService->getAll());
    }

    /**
     * Create a new product line.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return response()->json($product, 201);
    }

    /**
     * Update an existing product line.
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $updated = $this->productService->update($product, $request->validated());

        return response()->json($updated);
    }

    /**
     * Delete a product line.
     */
    public function destroy(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->productService->delete($product);

        return response()->json(['message' => 'Línea de producto eliminada']);
    }
}
