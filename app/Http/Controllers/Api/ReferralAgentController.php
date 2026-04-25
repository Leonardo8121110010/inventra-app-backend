<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReferralAgentRequest;
use App\Http\Requests\UpdateReferralAgentRequest;
use App\Services\ReferralAgentService;
use Illuminate\Http\JsonResponse;

class ReferralAgentController extends Controller
{
    protected ReferralAgentService $referralAgentService;

    public function __construct(ReferralAgentService $referralAgentService)
    {
        $this->referralAgentService = $referralAgentService;
    }

    /**
     * Get all referral agents.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->referralAgentService->getAll());
    }

    /**
     * Create a new referral agent.
     */
    public function store(StoreReferralAgentRequest $request): JsonResponse
    {
        $agent = $this->referralAgentService->create($request->validated());

        return response()->json($agent, 201);
    }

    /**
     * Update an existing referral agent.
     */
    public function update(UpdateReferralAgentRequest $request, string $id): JsonResponse
    {
        $agent = $this->referralAgentService->getAll()->firstWhere('id', $id);

        if (! $agent) {
            return response()->json(['message' => 'Agente no encontrado'], 404);
        }

        $updated = $this->referralAgentService->update($agent, $request->validated());

        return response()->json($updated);
    }
}
