<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CommissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommissionController extends Controller
{
    protected CommissionService $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    /**
     * Get all commissions with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $commissions = $this->commissionService->getAll(
            $request->query('agent_id'),
            $request->query('status')
        );

        return response()->json($commissions);
    }

    /**
     * Mark a commission as paid.
     */
    public function pay(string $id): JsonResponse
    {
        $commission = $this->commissionService->findById($id);
        $updated = $this->commissionService->markAsPaid($commission);

        return response()->json($updated);
    }

    /**
     * Get pending payout profile and applicable visit payouts for an agent.
     */
    public function payoutProfile(string $agentId): JsonResponse
    {
        $profile = $this->commissionService->getPayoutProfile($agentId);
        return response()->json($profile);
    }

    /**
     * Register a new arrival for an agent.
     */
    public function registerArrival(Request $request): JsonResponse
    {
        $request->validate([
            'agent_id' => 'required|string|exists:referral_agents,id',
        ]);

        $commission = $this->commissionService->registerArrival($request->agent_id);
        return response()->json($commission, 201);
    }

    /**
     * Get active unpaid arrivals (visits) for today.
     */
    public function activeVisits(): JsonResponse
    {
        $visits = $this->commissionService->getActiveVisits();
        return response()->json($visits);
    }
}
