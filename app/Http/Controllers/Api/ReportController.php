<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Movement;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * GET /api/reports/daily-inventory
     *
     * For each article, calculates:
     *   inicio      = actual stock at the START of the day (before any movements)
     *   movimientos = positive entries/adjustments during the day (stock received)
     *   ventas      = units sold during the day
     *   regalias    = negative adjustments with non-tasting notes
     *   tasting     = negative adjustments with "tasting" in notes
     *   final       = stock at the END of the day
     *
     * Balance: Final = Inicio + Movimientos - Ventas - Regalías - Tasting
     */
    public function dailyInventory(Request $request)
    {
        $dateFrom = $request->input('date_from', $request->input('date', now()->toDateString()));
        $dateTo   = $request->input('date_to', $request->input('date', now()->toDateString()));
        $branchId = $request->input('branch_id');

        $startOfDay = Carbon::parse($dateFrom)->startOfDay();
        $endOfDay   = Carbon::parse($dateTo)->endOfDay();

        // Si hay una caja abierta ese día (o cerrada), usamos su hora de apertura real.
        $cashRegisterQuery = \App\Models\CashRegister::whereDate('opened_at', $dateFrom);
        if ($branchId) {
            $cashRegisterQuery->where('branch_id', $branchId);
        }
        // Obtenemos la caja más antigua de ese día (por si hubieran varios turnos)
        $firstShift = $cashRegisterQuery->orderBy('opened_at', 'asc')->first();

        if ($firstShift && $firstShift->opened_at) {
            $startOfDay = Carbon::parse($firstShift->opened_at);
        }

        // Si la caja ya fue cerrada y queremos ser super precisos con el cierre, opcionalmente:
        // if ($firstShift && $firstShift->closed_at) {
        //     $endOfDay = Carbon::parse($firstShift->closed_at);
        // }

        // ── 1. Current live inventory ────────────────────────────────────
        $invQuery = Inventory::query();
        if ($branchId) {
            $invQuery->where('branch_id', $branchId);
        }
        $liveStock = [];
        foreach ($invQuery->get() as $row) {
            $liveStock[$row->product_id] = ($liveStock[$row->product_id] ?? 0) + (int) $row->qty;
        }

        // ── 2. Movements AFTER the requested date (to reverse-calc final) ─
        $afterQuery = Movement::where('date', '>', $endOfDay);
        if ($branchId) {
            $afterQuery->where('branch_id', $branchId);
        }
        $netAfter = [];
        foreach ($afterQuery->get() as $m) {
            $netAfter[$m->product_id] = ($netAfter[$m->product_id] ?? 0) + (int) $m->qty;
        }

        // ── 3. Movements ON the requested date ──────────────────────────
        $dayQuery = Movement::whereBetween('date', [$startOfDay, $endOfDay]);
        if ($branchId) {
            $dayQuery->where('branch_id', $branchId);
        }
        $dayByProduct = [];
        foreach ($dayQuery->get() as $m) {
            $dayByProduct[$m->product_id][] = $m;
        }

        // ── 4. Build rows for ALL articles ──────────────────────────────
        $articles = Article::with('supplier')->get();
        $rows = [];

        foreach ($articles as $article) {
            $pid = $article->id;

            // Final at end of requested date = live - everything after
            $final = ($liveStock[$pid] ?? 0) - ($netAfter[$pid] ?? 0);

            $dayMovs = $dayByProduct[$pid] ?? [];

            // ── Classify each movement ──────────────────────────────────
            $ventas      = 0;
            $movimientos = 0; // positive entries/adjustments during the day
            $movimientosDetail = [];
            $regalias    = 0;
            $regaliasDetail = [];
            $tasting     = 0;

            foreach ($dayMovs as $m) {
                $qty   = (int) $m->qty;
                $notes = trim($m->notes ?? '');

                if ($m->type === 'SALE') {
                    // Sales are negative qty
                    $ventas += abs($qty);

                } elseif ($qty > 0) {
                    // Positive movement = stock entered during the day (entries, positive adjustments)
                    $movimientos += $qty;
                    $movimientosDetail[] = [
                        'qty'   => $qty,
                        'notes' => $notes ?: $m->type,
                        'type'  => $m->type,
                        'user'  => $m->user,
                    ];

                } else {
                    // Negative non-sale movement
                    $absQty = abs($qty);

                    if (stripos($notes, 'tasting') !== false) {
                        $tasting += $absQty;
                    } else {
                        $regalias += $absQty;
                        if ($notes !== '') {
                            $regaliasDetail[] = [
                                'qty'   => $absQty,
                                'notes' => $notes,
                                'type'  => $m->type,
                                'user'  => $m->user,
                            ];
                        }
                    }
                }
            }

            // Inicio = Final - Movimientos + Ventas + Regalías + Tasting
            // (reverse the formula: Final = Inicio + Movimientos - Ventas - Regalías - Tasting)
            $inicio = $final - $movimientos + $ventas + $regalias + $tasting;

            $hasActivity = count($dayMovs) > 0;

            $rows[] = [
                'article_id'          => $pid,
                'name'                => $article->name,
                'sku'                 => $article->sku ?? '',
                'size_ml'             => $article->size_ml ?? null,
                'supplier'            => $article->supplier?->name ?? '—',
                'inicio'              => $inicio,
                'movimientos'         => $movimientos,
                'movimientos_detail'  => $movimientosDetail,
                'ventas'              => $ventas,
                'regalias'            => $regalias,
                'regalias_detail'     => $regaliasDetail,
                'tasting'             => $tasting,
                'final'               => $final,
                'has_activity'        => $hasActivity,
            ];
        }

        usort($rows, fn($a, $b) => strcmp($a['name'], $b['name']));

        return response()->json([
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
            'branch'    => $branchId ? (Branch::find($branchId)?->name ?? $branchId) : 'Todas las sucursales',
            'articles'  => $rows,
        ]);
    }

    /**
     * GET /api/reports/commissions
     * Query params: date_from, date_to, branch_id, status (pending|paid|all)
     */
    public function commissions(Request $request)
    {
        $dateFrom = $request->input('date_from', $request->input('date', now()->toDateString()));
        $dateTo   = $request->input('date_to', $request->input('date', now()->toDateString()));
        $branchId = $request->input('branch_id');
        $statusFilter = $request->input('status', 'all'); // pending | paid | all

        $startOfDay = Carbon::parse($dateFrom)->startOfDay();
        $endOfDay   = Carbon::parse($dateTo)->endOfDay();

        $query = \App\Models\Commission::with(['agent.agentTypes', 'sale', 'rule'])
            ->whereBetween('date', [$startOfDay, $endOfDay]);

        if ($branchId) {
            $query->whereHas('sale', function ($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });
        }

        if ($statusFilter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($statusFilter === 'paid') {
            $query->where('status', 'paid');
        }
        // 'all' → no extra filter

        $commissions = $query->get();
        $grouped = [];
        $grandTotal = 0;
        $grandPending = 0;
        $grandPaid = 0;

        foreach ($commissions as $comm) {
            $agent = $comm->agent;

            $agentTypeName = 'General';
            if ($agent && $agent->agentTypes->count() > 0) {
                $agentTypeName = $agent->agentTypes->first()->name;
            }

            if (!isset($grouped[$agentTypeName])) {
                $grouped[$agentTypeName] = [
                    'agent_type'         => $agentTypeName,
                    'total_commission'   => 0,
                    'total_pending'      => 0,
                    'total_paid'         => 0,
                    'total_sales_amount' => 0,
                    'items'              => []
                ];
            }

            $grouped[$agentTypeName]['total_commission']   += $comm->commission_amount;
            $grouped[$agentTypeName]['total_sales_amount'] += $comm->sale_amount;
            $grandTotal += $comm->commission_amount;

            if ($comm->status === 'pending') {
                $grouped[$agentTypeName]['total_pending'] += $comm->commission_amount;
                $grandPending += $comm->commission_amount;
            } else {
                $grouped[$agentTypeName]['total_paid'] += $comm->commission_amount;
                $grandPaid += $comm->commission_amount;
            }

            // Resolve branch from related sale
            $salebranchId = $comm->sale?->branch_id;
            $branchName = $salebranchId ? (\App\Models\Branch::find($salebranchId)?->name ?? $salebranchId) : '—';

            $grouped[$agentTypeName]['items'][] = [
                'id'                => $comm->id,
                'agent_name'        => $agent ? $agent->name : 'Desconocido',
                'sale_id'           => $comm->sale_id,
                'sale_amount'       => $comm->sale_amount,
                'commission_amount' => $comm->commission_amount,
                'motive'            => $comm->rule?->trigger ?? 'sale',
                'date'              => $comm->date->toDateTimeString(),
                'status'            => $comm->status ?? 'pending',
                'branch_name'       => $branchName,
            ];
        }

        return response()->json([
            'date_from'     => $dateFrom,
            'date_to'       => $dateTo,
            'branch'        => $branchId ? (Branch::find($branchId)?->name ?? $branchId) : 'Todas las sucursales',
            'status_filter' => $statusFilter,
            'groups'        => array_values($grouped),
            'grand_total'   => $grandTotal,
            'grand_pending' => $grandPending,
            'grand_paid'    => $grandPaid,
        ]);
    }

    /**
     * GET /api/reports/closure
     * Query params: date (Y-m-d), branch_id
     */
    public function closure(Request $request)
    {
        $date     = $request->input('date', now()->toDateString());
        $branchId = $request->input('branch_id');

        if (!$branchId) {
            return response()->json(['message' => 'branch_id is required'], 400);
        }

        $register = \App\Models\CashRegister::where('branch_id', $branchId)
            ->whereDate('opened_at', $date)
            ->orderBy('opened_at', 'desc')
            ->first();

        // Even if no register is found, we might want to still return stats for the day if they had standalone sales?
        // Let's bound by the register if it exists, otherwise full day.
        $start = $register ? $register->opened_at : Carbon::parse($date)->startOfDay();
        $end   = $register ? ($register->closed_at ?? now()) : Carbon::parse($date)->endOfDay();

        $query = \App\Models\Sale::with(['payments', 'commissions.agent.agentTypes'])->whereBetween('date', [$start, $end])->where('branch_id', $branchId);
        $sales = $query->get();

        $report = [
            'date' => $date,
            'branch' => \App\Models\Branch::find($branchId)?->name ?? '',
            'commissions' => [],
            'payments' => [
                'card' => 0,
                'transfer' => 0,
            ],
            'cash' => [],
            'total_sales_mxn' => 0,
        ];

        $commsGrouped = [];

        foreach ($sales as $sale) {
            $report['total_sales_mxn'] += $sale->total_mxn;

            // Payments
            foreach ($sale->payments as $payment) {
                if ($payment->method === 'card') {
                    $report['payments']['card'] += $payment->amount_mxn;
                } elseif ($payment->method === 'transfer') {
                    $report['payments']['transfer'] += $payment->amount_mxn;
                } elseif ($payment->method === 'cash') {
                    $currency = $payment->currency ?: 'MXN';
                    if (!isset($report['cash'][$currency])) {
                        $report['cash'][$currency] = [
                            'amount' => 0,
                            'amount_mxn' => 0
                        ];
                    }
                    $report['cash'][$currency]['amount'] += $payment->amount; // raw currency amount
                    $report['cash'][$currency]['amount_mxn'] += $payment->amount_mxn; // converted MXN amount
                }
            }

            // Commissions
            foreach ($sale->commissions as $comm) {
                $agent = $comm->agent;
                $agentTypeName = 'General';
                if ($agent && $agent->agentTypes->count() > 0) {
                    $agentTypeName = $agent->agentTypes->first()->name;
                }
                $agentName = $agent ? $agent->name : 'Desconocido';

                if (!isset($commsGrouped[$agentTypeName])) {
                    $commsGrouped[$agentTypeName] = [
                        'agency_name' => $agentTypeName,
                        'total_mxn' => 0,
                        'agents' => []
                    ];
                }

                if (!isset($commsGrouped[$agentTypeName]['agents'][$agentName])) {
                    $commsGrouped[$agentTypeName]['agents'][$agentName] = [
                        'agent_name' => $agentName,
                        'total_mxn' => 0,
                        'details' => []
                    ];
                }

                $commsGrouped[$agentTypeName]['total_mxn'] += $comm->commission_amount;
                $commsGrouped[$agentTypeName]['agents'][$agentName]['total_mxn'] += $comm->commission_amount;
                
                $commsGrouped[$agentTypeName]['agents'][$agentName]['details'][] = [
                    'sale_id' => $sale->id,
                    'currency_paid' => $sale->currency ?? 'MXN',
                    'amount_paid' => $comm->sale_amount,
                    'commission_mxn' => $comm->commission_amount,
                ];
            }
        }

        $formattedComms = [];
        foreach ($commsGrouped as $agency) {
            $agency['agents'] = array_values($agency['agents']);
            $formattedComms[] = $agency;
        }

        $report['commissions'] = $formattedComms;

        return response()->json($report);
    }

    /**
     * GET /api/reports/general-inventory
     * Returns current live stock per article, broken down by branch.
     * Accepts optional: branch_ids (comma-separated string)
     */
    public function generalInventory(Request $request)
    {
        $branchIdsRaw = $request->input('branch_ids', '');
        $branchIds = array_filter(array_map('trim', explode(',', $branchIdsRaw)));

        // Resolve the list of branches we care about
        $branchQuery = Branch::query();
        if (!empty($branchIds)) {
            $branchQuery->whereIn('id', $branchIds);
        }
        $branches = $branchQuery->get();

        // Fetch inventory grouped by product + branch
        $invQuery = \App\Models\Inventory::query();
        if (!empty($branchIds)) {
            $invQuery->whereIn('branch_id', $branchIds);
        }

        $stockMap = []; // stockMap[product_id][branch_id] = qty
        foreach ($invQuery->get() as $row) {
            $stockMap[$row->product_id][$row->branch_id] = (int) $row->qty;
        }

        // Build rows for ALL articles
        $articles = Article::with('supplier')->get();
        $rows = [];

        foreach ($articles as $article) {
            $pid = $article->id;
            $stockByBranch = [];
            $total = 0;

            foreach ($branches as $branch) {
                $qty = $stockMap[$pid][$branch->id] ?? 0;
                $stockByBranch[(string) $branch->id] = $qty;
                $total += $qty;
            }

            $rows[] = [
                'article_id'       => $pid,
                'name'             => $article->name,
                'sku'              => $article->sku ?? '',
                'supplier'         => $article->supplier?->name ?? '—',
                'stock_by_branch'  => $stockByBranch,
                'total'            => $total,
            ];
        }

        usort($rows, fn($a, $b) => strcmp($a['name'], $b['name']));

        return response()->json([
            'branches' => $branches->map(fn($b) => ['id' => (string) $b->id, 'name' => $b->name])->values(),
            'articles' => $rows,
        ]);
    }
}
