<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommissionRuleRequest;
use App\Http\Requests\UpdateCommissionRuleRequest;
use App\Services\CommissionRuleService;
use Illuminate\Http\JsonResponse;

class CommissionRuleController extends Controller
{
    protected CommissionRuleService $commissionRuleService;

    public function __construct(CommissionRuleService $commissionRuleService)
    {
        $this->commissionRuleService = $commissionRuleService;
    }

    /**
     * Get all commission rules.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->commissionRuleService->getAll());
    }

    /**
     * Create a new commission rule.
     */
    public function store(StoreCommissionRuleRequest $request): JsonResponse
    {
        $rule = $this->commissionRuleService->create($request->validated());

        return response()->json($rule, 201);
    }

    /**
     * Update an existing commission rule.
     */
    public function update(UpdateCommissionRuleRequest $request, string $id): JsonResponse
    {
        $rule = $this->commissionRuleService->getAll()->firstWhere('id', $id);

        if (! $rule) {
            return response()->json(['message' => 'Regla no encontrada'], 404);
        }

        $updated = $this->commissionRuleService->update($rule, $request->validated());

        return response()->json($updated);
    }
}
