<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\ReferralAgent;
use Illuminate\Database\Eloquent\Collection;

class CommissionService
{
    /**
     * Get all commissions with optional filters.
     */
    public function getAll(?string $agentId = null, ?string $status = null): Collection
    {
        $query = Commission::with(['agent', 'rule'])->orderByDesc('date');

        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    /**
     * Find a commission by ID.
     */
    public function findById(string $id): Commission
    {
        return Commission::findOrFail($id);
    }

    /**
     * Mark a commission as paid.
     */
    public function markAsPaid(Commission $commission): Commission
    {
        $commission->update(['status' => 'paid']);

        return $commission;
    }

    /**
     * Get the payout profile for an agent, calculating pending commissions
     * and fetching available visit triggers.
     */
    public function getPayoutProfile(string $agentId): array
    {
        $agent = ReferralAgent::with('agentTypes')->findOrFail($agentId);

        $pendingCommissions = Commission::with('sale')
            ->where('agent_id', $agentId)
            ->where('status', 'pending')
            ->get();

        $typeIds = $agent->agentTypes->pluck('id')->toArray();

        $visitRules = CommissionRule::where('trigger', 'visit')
            ->where('active', true)
            ->where(function ($q) use ($agentId, $typeIds) {
                // If it is globally assigned to the agent specifically
                $q->where('agent_id', $agentId)
                  // Or globally applied to any of their agencies
                  ->orWhereIn('agent_type_id', $typeIds);
            })->get();

        return [
            'pending_commissions' => $pendingCommissions,
            'visit_rules'         => $visitRules,
        ];
    }

    /**
     * Register a new arrival for an agent.
     * Creates a pending commission so it can be selected during checkout.
     */
    public function registerArrival(string $agentId): Commission
    {
        $agent = ReferralAgent::with('agentTypes')->findOrFail($agentId);
        $typeIds = $agent->agentTypes->pluck('id')->toArray();

        // Get visit rule if any (highest priority first, which usually means by agent_id then type_id)
        $visitRule = CommissionRule::where('trigger', 'visit')
            ->where('active', true)
            ->where(function ($q) use ($agentId, $typeIds) {
                $q->where('agent_id', $agentId)
                  ->orWhereIn('agent_type_id', $typeIds);
            })->first();

        $commission = Commission::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'agent_id' => $agentId,
            'sale_id' => null,
            'sale_amount' => 0,
            'commission_amount' => $visitRule ? $visitRule->value : 0,
            'date' => now(),
            'status' => 'pending',
            'rule_id' => $visitRule ? $visitRule->id : null,
        ]);

        $commission->load('agent.agentTypes');
        return $commission;
    }

    /**
     * Get active unpaid arrivals (visits) for today.
     * These are commissions with no sale_id that haven't been paid out.
     */
    public function getActiveVisits(): Collection
    {
        return Commission::with(['agent.agentTypes'])
            ->whereNull('sale_id')
            ->where('status', 'pending')
            ->whereDate('date', now()->toDateString())
            ->orderBy('date', 'desc')
            ->get();
    }
}
