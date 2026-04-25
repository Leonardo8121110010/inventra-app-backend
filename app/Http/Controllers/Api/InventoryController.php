<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustInventoryRequest;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get all inventory as nested object.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->inventoryService->getAll());
    }

    /**
     * Get inventory for a specific branch.
     */
    public function branch(string $branch): JsonResponse
    {
        return response()->json($this->inventoryService->getBranch($branch));
    }

    /**
     * Manually adjust stock for a branch-product pair.
     */
    public function adjust(AdjustInventoryRequest $request, string $branch, string $product): JsonResponse
    {
        $row = $this->inventoryService->adjust($branch, $product, $request->validated('qty'));

        return response()->json($row);
    }
}
