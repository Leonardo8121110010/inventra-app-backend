<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Inventory;
use App\Models\Movement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\CashRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['items', 'payments', 'referralAgents'])->orderByDesc('date');
        $user = $request->user();

        // Non-admin users can only see sales from their assigned branches
        if ($user->role !== 'admin' && strtolower($user->role) !== 'admin') {
            $branchIds = $user->branches()->pluck('branch_id')->toArray();
            if (empty($branchIds)) {
                $branchIds = [$user->branch_id];
            }
            $query->whereIn('branch_id', $branchIds);
        }

        if ($request->filled('branch_id'))
            $query->where('branch_id', $request->branch_id);
        if ($request->filled('date_from'))
            $query->whereDate('date', '>=', $request->date_from);
        if ($request->filled('date_to'))
            $query->whereDate('date', '<=', $request->date_to);

        if ($request->boolean('paginate')) {
            return response()->json($query->paginate($request->input('per_page', 50)));
        }

        return response()->json($query->limit(500)->get());
    }

    public function show(string $id)
    {
        return response()->json(Sale::with('items')->findOrFail($id));
    }

    public function store(Request $request)
    {
        // Auto-inject branch_id for non-admin users
        $user = $request->user();
        if (!$request->has('branch_id') && ($user->role !== 'admin' && strtolower($user->role) !== 'admin')) {
            $request->merge(['branch_id' => $user->branch_id]);
        }

        $data = $request->validate([
            'id' => 'required|string|unique:sales,id',
            'branch_id' => 'required|string|exists:branches,id',
            'date' => 'required|date',
            'total' => 'required|numeric|min:0',
            'total_mxn' => 'required|numeric|min:0',
            'subtotal' => 'numeric|min:0',
            'discount' => 'numeric|min:0',
            'currency' => 'required|in:MXN,USD,EUR,CAD',
            'referral_agents' => 'nullable|array',
            'referral_agents.*.agent_id' => 'required|string|exists:referral_agents,id',
            'referral_agents.*.agent_type_id' => 'nullable|string',
            // Split payments
            'payments' => 'required|array|min:1',
            'payments.*.method' => 'required|in:cash,card,transfer',
            'payments.*.currency' => 'required|in:MXN,USD,EUR,CAD',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.amount_mxn' => 'required|numeric|min:0',
            'payments.*.exchange_rate' => 'numeric|min:0',
            // Items
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.price_in_currency' => 'numeric|min:0',
            'items.*.currency' => 'required|in:MXN,USD,EUR,CAD',
        ]);

        // ── Anti-overpayment guard for electronic methods ─────────────────────
        // Card and transfer payments must not exceed the sale total — no change
        // is given for electronic transactions. We allow 0.01 MXN tolerance for
        // rounding errors in multi-currency scenarios.
        $electronicPaidMxn = collect($data['payments'])
            ->filter(fn($p) => in_array($p['method'], ['card', 'transfer']))
            ->sum('amount_mxn');

        if ($electronicPaidMxn > $data['total_mxn'] + 0.01) {
            return response()->json([
                'message' => 'El pago electrónico supera el total de la venta. Los pagos con tarjeta o transferencia deben ser exactos.',
                'electronic_paid_mxn' => $electronicPaidMxn,
                'total_mxn' => $data['total_mxn'],
            ], 422);
        }

        $register = CashRegister::where('branch_id', $data['branch_id'])
            ->where('status', 'open')
            ->first();

        // Derive primary payment method from first payment (for quick-access / backwards compat)
        $primaryMethod = $data['payments'][0]['method'];

        DB::transaction(function () use ($data, $request, $register, $primaryMethod) {
            // 1. Create sale
            $sale = Sale::create([
                'id' => $data['id'],
                'branch_id' => $data['branch_id'],
                'user_id' => $request->user()->id,
                'date' => now(),
                'total' => $data['total'],
                'total_mxn' => $data['total_mxn'],
                'subtotal' => $data['subtotal'] ?? $data['total'],
                'discount' => $data['discount'] ?? 0,
                'payment' => $primaryMethod,
                'currency' => $data['currency'],
                'referral_agent_id' => null,
                'cash_register_id' => $register ? $register->id : null,
            ]);

            // 2. Create payment records
            foreach ($data['payments'] as $p) {
                SalePayment::create([
                    'id' => 'sp' . time() . rand(100, 999),
                    'sale_id' => $sale->id,
                    'method' => $p['method'],
                    'currency' => $p['currency'],
                    'amount' => $p['amount'],
                    'amount_mxn' => $p['amount_mxn'],
                    'exchange_rate' => $p['exchange_rate'] ?? 1,
                ]);
            }

            // 3. Create items, deduct inventory, create movements
            foreach ($data['items'] as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'price' => $item['price'],
                    'price_in_currency' => $item['price_in_currency'] ?? $item['price'],
                    'currency' => $item['currency'],
                ]);

                // Deduct inventory
                $inv = Inventory::firstOrCreate(
                    ['branch_id' => $sale->branch_id, 'product_id' => $item['product_id']],
                    ['qty' => 0]
                );
                $inv->qty = max(0, $inv->qty - $item['qty']);
                $inv->save();

                // Movement record
                Movement::create([
                    'id' => 'mv' . time() . rand(100, 999),
                    'type' => 'SALE',
                    'product_id' => $item['product_id'],
                    'branch_id' => $sale->branch_id,
                    'qty' => -$item['qty'],
                    'date' => $sale->date,
                    'user' => $request->user()->name,
                    'notes' => 'Venta #' . $sale->id,
                ]);
            }

            // 4. Generate commissions if referral agents are set
            if (!empty($data['referral_agents'])) {
                foreach ($data['referral_agents'] as $agentData) {
                    $agentId = $agentData['agent_id'];
                    $agentTypeId = $agentData['agent_type_id'] ?? '';

                    if (empty($agentTypeId)) {
                        $agent = \App\Models\ReferralAgent::find($agentId);
                        $firstType = $agent ? $agent->agentTypes->first() : null;
                        $agentTypeId = $firstType ? $firstType->id : '';
                    }

                    DB::table('sale_referral_agents')->insert([
                        'sale_id' => $sale->id,
                        'referral_agent_id' => $agentId,
                        'agent_type_id' => $agentTypeId ?: null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->generateCommissions($sale, $data['items'], $agentId, $agentTypeId);
                }
            }
        });

        return response()->json(
            Sale::with('items', 'referralAgents', 'payments')->find($data['id']),
            201
        );
    }

    /**
     * Assign a referral agent to an existing sale retroactively and generate commissions.
     */
    public function assignAgent(Request $request, string $id)
    {
        $request->validate([
            'agent_id' => 'required|string|exists:referral_agents,id',
        ]);

        $sale = Sale::with('items')->findOrFail($id);
        $agentId = $request->agent_id;

        // Check if agent is already assigned to this sale to prevent duplicates
        $exists = DB::table('sale_referral_agents')
            ->where('sale_id', $sale->id)
            ->where('referral_agent_id', $agentId)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'El agente ya está vinculado a esta venta'], 422);
        }

        $agent = \App\Models\ReferralAgent::with('agentTypes')->find($agentId);
        $firstType = $agent ? $agent->agentTypes->first() : null;
        $agentTypeId = $firstType ? $firstType->id : '';

        DB::transaction(function () use ($sale, $agentId, $agentTypeId) {
            DB::table('sale_referral_agents')->insert([
                'sale_id' => $sale->id,
                'referral_agent_id' => $agentId,
                'agent_type_id' => $agentTypeId ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->generateCommissions($sale, $sale->items->toArray(), $agentId, $agentTypeId);
        });

        // Load the relationship before returning so the frontend sees the update
        $sale->load('referralAgents');

        return response()->json($sale);
    }

    // ── Commission logic ─────────────────────────────────────────────────────

    private function generateCommissions(Sale $sale, array $items, string $agentId, string $agentTypeId): void
    {
        $rules = CommissionRule::where('active', true)->get();
        $totalCommission = 0;
        $appliedRuleIds = [];

        // Note: Visit rules are no longer evaluated during checkout.
        // They are generated manually via the "Registrar Llegada" endpoint.

        // Sale rules — per item with product-level priority
        if ($sale->total_mxn > 0) {
            foreach ($items as $item) {
                $pid = $item['product_id'] ?? ($item['id'] ?? '');
                $fixedSaleRule = $this->pickRule($agentId, $agentTypeId, $pid, 'sale', 'fixed', $rules);
                $percentSaleRule = $this->pickRule($agentId, $agentTypeId, $pid, 'sale', 'percentage', $rules);

                if ($fixedSaleRule) {
                    $totalCommission += ($fixedSaleRule->value * $item['qty']);
                    if (!in_array($fixedSaleRule->id, $appliedRuleIds))
                        $appliedRuleIds[] = $fixedSaleRule->id;
                }
                if ($percentSaleRule) {
                    $lineMxn = $item['price'] * $item['qty'];
                    $totalCommission += ($lineMxn * ($percentSaleRule->value / 100));
                    if (!in_array($percentSaleRule->id, $appliedRuleIds))
                        $appliedRuleIds[] = $percentSaleRule->id;
                }
            }
        }

        if ($totalCommission > 0) {
            Commission::create([
                'id' => 'com' . time() . 'sum' . rand(10, 99),
                'agent_id' => $agentId,
                'sale_id' => $sale->id,
                'sale_amount' => $sale->total_mxn,
                'commission_amount' => round($totalCommission, 2),
                'date' => $sale->date,
                'status' => 'pending',
                'rule_id' => implode(',', $appliedRuleIds),
            ]);
        }

        // ── Volume Bonus (Bono x N botellas) ─────────────────────────────────
        $volumeRules = $rules->where('trigger', 'volume');
        if ($volumeRules->isNotEmpty()) {
            foreach ($volumeRules as $volumeRule) {
                // Rule applicability checks:
                // 1. If agent_id is set, it MUST match the sale's referral agent
                if ($volumeRule->agent_id !== '' && $volumeRule->agent_id !== $agentId) {
                    continue;
                }
                // 2. If agent_id is empty but agent_type_id is set, it MUST match the agent type
                if ($volumeRule->agent_id === '' && $volumeRule->agent_type_id !== '' && $volumeRule->agent_type_id !== $agentTypeId) {
                    continue;
                }

                if ($volumeRule->volume_threshold > 0) {
                    $threshold = $volumeRule->volume_threshold;

                    // Query past sales for this agent that match the rule's criteria
                    $query = SaleItem::whereHas('sale.referralAgents', function ($q) use ($agentId) {
                        $q->where('referral_agent_id', $agentId);
                    });

                    // Date filters based on rule period
                    $saleDate = \Carbon\Carbon::parse($sale->date);
                    if ($volumeRule->period === 'daily') {
                        $query->whereHas('sale', function ($q) use ($saleDate) {
                            $q->whereDate('date', $saleDate->format('Y-m-d'));
                        });
                    } elseif ($volumeRule->period === 'weekly') {
                        $start = $saleDate->copy()->startOfWeek()->format('Y-m-d H:i:s');
                        $end = $saleDate->copy()->endOfWeek()->format('Y-m-d H:i:s');
                        $query->whereHas('sale', function ($q) use ($start, $end) {
                            $q->whereBetween('date', [$start, $end]);
                        });
                    } elseif ($volumeRule->period === 'monthly') {
                        $query->whereHas('sale', function ($q) use ($saleDate) {
                            $q->whereYear('date', $saleDate->year)
                                ->whereMonth('date', $saleDate->month);
                        });
                    }

                    // Product filter based on rule
                    if ($volumeRule->product_id !== '') {
                        $query->where('product_id', $volumeRule->product_id);
                    }

                    $totalBottles = $query->sum('qty');

                    // Determine current sale's contribution to this specific rule
                    $currentSaleBottles = 0;
                    foreach ($items as $item) {
                        $pid = $item['product_id'] ?? ($item['id'] ?? '');
                        if ($volumeRule->product_id === '' || $pid === $volumeRule->product_id) {
                            $currentSaleBottles += $item['qty'];
                        }
                    }

                    // Only process if the current sale actually contributed to the rule
                    if ($currentSaleBottles > 0) {
                        $previousBottles = $totalBottles - $currentSaleBottles;

                        // Como el bono es único por periodo, el máximo de bonos en el ciclo es 1.
                        $previousBonuses = ((int) $previousBottles >= $threshold) ? 1 : 0;
                        $currentBonuses = ((int) $totalBottles >= $threshold) ? 1 : 0;
                        $newBonuses = $currentBonuses - $previousBonuses;

                        if ($newBonuses > 0) {
                            $bonusAmount = $newBonuses * $volumeRule->value;
                            Commission::create([
                                'id' => 'com' . time() . 'vol' . rand(10, 999),
                                'agent_id' => $agentId,
                                'sale_id' => $sale->id,
                                'sale_amount' => 0,
                                'commission_amount' => round($bonusAmount, 2),
                                'date' => $sale->date,
                                'status' => 'pending',
                                'rule_id' => $volumeRule->id,
                            ]);
                        }
                    }
                }
            }
        }
    }

    private function pickRule(string $agentId, string $agentTypeId, string $productId, string $trigger, string $commType, $rules)
    {
        // 1. Filter by trigger and commission type
        $active = $rules->where('trigger', $trigger)->where('commission_type', $commType);

        /**
         * Scoring System (Specificity):
         * 5: Agent ID + Product ID
         * 4: Agent ID (no product)
         * 3: Agent Type + Product ID
         * 2: Agent Type (no product)
         * 1: Product Specific (no agent/type)
         * 0: Global (no agent/type/product)
         */
        $scored = $active->map(function ($r) use ($agentId, $agentTypeId, $productId) {
            $rAgent = trim((string) $r->agent_id);
            $rType = trim((string) $r->agent_type_id);
            $rProd = trim((string) $r->product_id);

            $score = -1;

            // Priority 5: Agent + Product
            if ($rAgent === $agentId && $rProd === $productId && $agentId !== '' && $productId !== '') {
                $score = 5;
            }
            // Priority 4: Agent specific (no product)
            elseif ($rAgent === $agentId && $rProd === '' && $agentId !== '') {
                $score = 4;
            }
            // Priority 3: Agent Type + Product  (solo si la regla NO tiene agente específico)
            elseif ($rAgent === '' && $rType === $agentTypeId && $rProd === $productId && $agentTypeId !== '' && $productId !== '') {
                $score = 3;
            }
            // Priority 2: Agent Type (no product, y la regla NO tiene agente específico)
            elseif ($rAgent === '' && $rType === $agentTypeId && $rProd === '' && $agentTypeId !== '') {
                $score = 2;
            }
            // Priority 1: Global + Product specific (sin agente ni tipo)
            elseif ($rProd === $productId && $rAgent === '' && $rType === '' && $productId !== '') {
                $score = 1;
            }
            // Priority 0: Global (sin agente, tipo ni producto)
            elseif ($rAgent === '' && $rType === '' && $rProd === '') {
                $score = 0;
            }

            $r->priority_score = $score;
            return $r;
        })->where('priority_score', '>=', 0);

        return $scored->sortByDesc('priority_score')->first();
    }
}