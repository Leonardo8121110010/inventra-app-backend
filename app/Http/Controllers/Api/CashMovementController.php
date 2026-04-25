<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashMovement;
use App\Models\CashMovementMotive;
use App\Models\CashRegister;
use App\Models\Commission;
use App\Models\CommissionRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashMovementController extends Controller
{
    /**
     * List movements for a given cash register.
     */
    public function index(Request $request): JsonResponse
    {
        $registerId = $request->query('register_id');

        $query = CashMovement::with(['motive', 'user:id,name,avatar', 'referralAgent:id,name'])
            ->orderBy('created_at', 'desc');

        if ($registerId) {
            $query->where('cash_register_id', $registerId);
        } else {
            // Default: active register for this branch
            $branchId = $request->query('branch_id');
            $active = CashRegister::where('branch_id', $branchId)
                ->where('status', 'open')
                ->first();

            if (!$active) {
                return response()->json([]);
            }

            $query->where('cash_register_id', $active->id);
        }

        return response()->json($query->get()->map(fn($m) => $this->format($m)));
    }

    /**
     * Create a new cash movement.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cash_register_id'  => 'required|integer|exists:cash_registers,id',
            'branch_id'         => 'required|string',
            'type'              => 'required|in:in,out',
            'amount'            => 'required|numeric|min:0.01',
            'amount_mxn'        => 'required|numeric|min:0',
            'currency'          => 'required|string|max:10',
            'motive_id'         => 'nullable|string|exists:cash_movement_motives,id',
            'description'       => 'nullable|string|max:500',
            'sale_id'           => 'nullable|string|exists:sales,id',
            'referral_agent_id' => 'nullable|string|exists:referral_agents,id',
            'is_payout'         => 'nullable|boolean',
            'commission_ids'    => 'nullable|array',
            'commission_ids.*'  => 'exists:commissions,id',
            'visit_rule_id'     => 'nullable|exists:commission_rules,id',
        ]);

        $data['user_id'] = $request->user()->id;

        $movement = CashMovement::create([
            'cash_register_id'  => $data['cash_register_id'],
            'branch_id'         => $data['branch_id'],
            'type'              => $data['type'],
            'amount'            => $data['amount'],
            'amount_mxn'        => $data['amount_mxn'],
            'currency'          => $data['currency'],
            'motive_id'         => $data['motive_id'] ?? null,
            'description'       => $data['description'] ?? null,
            'sale_id'           => $data['sale_id'] ?? null,
            'referral_agent_id' => $data['referral_agent_id'] ?? null,
            'user_id'           => $data['user_id'],
        ]);

        // Handle Payout Logic
        if ($request->boolean('is_payout') && $data['referral_agent_id']) {
            // 1. Mark existing sales commissions as paid
            if (!empty($data['commission_ids'])) {
                Commission::whereIn('id', $data['commission_ids'])->update(['status' => 'paid']);
            }

            // 2. Register an instantaneous 'visit' commission record
            if (!empty($data['visit_rule_id'])) {
                $rule = CommissionRule::find($data['visit_rule_id']);
                if ($rule) {
                    Commission::create([
                        'id'                => uniqid(),
                        'agent_id'          => $data['referral_agent_id'],
                        'sale_id'           => null,
                        'sale_amount'       => null,
                        'commission_amount' => $rule->value,
                        'date'              => now(),
                        'status'            => 'paid',
                        'rule_id'           => $rule->id,
                    ]);
                }
            }
        }

        $movement->load(['motive', 'user:id,name,avatar', 'referralAgent:id,name']);

        return response()->json($this->format($movement), 201);
    }

    /**
     * Delete a movement (only while the register is open).
     */
    public function destroy(string $id): JsonResponse
    {
        $movement = CashMovement::findOrFail($id);

        $register = CashRegister::find($movement->cash_register_id);
        if (!$register || $register->status !== 'open') {
            return response()->json(['message' => 'No se puede eliminar un movimiento de una caja cerrada.'], 422);
        }

        $movement->delete();

        return response()->json(['message' => 'Movimiento eliminado.']);
    }

    /**
     * List all active motives.
     */
    public function motives(): JsonResponse
    {
        $motives = CashMovementMotive::where('active', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json($motives);
    }

    private function format(CashMovement $m): array
    {
        return [
            'id'                => $m->id,
            'cash_register_id'  => $m->cash_register_id,
            'branch_id'         => $m->branch_id,
            'type'              => $m->type,
            'amount'            => $m->amount,
            'amount_mxn'        => $m->amount_mxn,
            'currency'          => $m->currency,
            'motive_id'         => $m->motive_id,
            'motive'            => $m->motive ? [
                'id'                 => $m->motive->id,
                'name'               => $m->motive->name,
                'icon'               => $m->motive->icon,
                'applies_commission' => $m->motive->applies_commission,
            ] : null,
            'description'       => $m->description,
            'sale_id'           => $m->sale_id,
            'referral_agent_id' => $m->referral_agent_id,
            'referral_agent'    => $m->referralAgent ? ['id' => $m->referralAgent->id, 'name' => $m->referralAgent->name] : null,
            'user'              => $m->user ? ['id' => $m->user->id, 'name' => $m->user->name, 'avatar' => $m->user->avatar] : null,
            'created_at'        => $m->created_at?->toISOString(),
        ];
    }
}
