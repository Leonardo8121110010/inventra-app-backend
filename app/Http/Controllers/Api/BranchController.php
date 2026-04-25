<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\User;
use App\Services\BranchService;
use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    protected BranchService $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    /**
     * Get all branches.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->branchService->getAll());
    }

    /**
     * Create a new branch.
     */
    public function store(StoreBranchRequest $request): JsonResponse
    {
        $branch = $this->branchService->create($request->validated());

        return response()->json($branch, 201);
    }

    /**
     * Update an existing branch.
     */
    public function update(UpdateBranchRequest $request, string $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);
        $updated = $this->branchService->update($branch, $request->validated());

        return response()->json($updated);
    }

    /**
     * Delete a branch.
     */
    public function destroy(string $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        if (Inventory::where('branch_id', $id)->exists()
            || User::where('branch_id', $id)->exists()
            || Sale::where('branch_id', $id)->exists()) {
            return response()->json(['message' => 'No se puede eliminar, tiene datos relacionados (inventario, usuarios o ventas)'], 400);
        }

        $this->branchService->delete($branch);

        return response()->json(null, 204);
    }
}
