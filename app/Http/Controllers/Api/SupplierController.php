<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;

class SupplierController extends Controller
{
    protected SupplierService $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    /**
     * Get all active suppliers.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->supplierService->getAll());
    }

    /**
     * Create a new supplier.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $supplier = $this->supplierService->create($request->validated());

        return response()->json($supplier, 201);
    }

    /**
     * Get a single supplier by ID.
     */
    public function show(string $id): JsonResponse
    {
        $supplier = $this->supplierService->findById($id);

        return response()->json($supplier);
    }

    /**
     * Update an existing supplier.
     */
    public function update(UpdateSupplierRequest $request, string $id): JsonResponse
    {
        $supplier = $this->supplierService->findById($id);
        $updated = $this->supplierService->update($supplier, $request->validated());

        return response()->json($updated);
    }

    /**
     * Deactivate a supplier (soft delete via active flag).
     */
    public function destroy(string $id): JsonResponse
    {
        $supplier = $this->supplierService->findById($id);
        $this->supplierService->deactivate($supplier);

        return response()->json(['message' => 'Proveedor desactivado correctamente']);
    }
}
