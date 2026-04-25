<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Models\Sale;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CashRegisterController extends Controller
{
    /**
     * Get the currently open cash register for a given branch.
     */
    public function current(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id'
        ]);

        $register = CashRegister::where('branch_id', $request->branch_id)
            ->where('status', 'open')
            ->first();

        if (!$register) {
            return response()->json(['message' => 'No active shift'], 404);
        }

        // Calculate current expected balances based on sale payments
        $cashPayments = \App\Models\SalePayment::where('method', 'cash')
            ->whereHas('sale', function ($q) use ($register) {
                $q->where('cash_register_id', $register->id);
            })->get();

        $expectedBalances = $register->opening_balances ?? ['MXN' => $register->opening_amount ?? 0, 'USD' => 0];

        foreach ($cashPayments as $payment) {
            $currency = $payment->currency ?: 'MXN';
            if (!isset($expectedBalances[$currency])) {
                $expectedBalances[$currency] = 0;
            }
            $expectedBalances[$currency] += $payment->amount;
        }

        // Include cash movements
        $movements = \App\Models\CashMovement::where('cash_register_id', $register->id)->get();
        foreach ($movements as $m) {
            $curr = $m->currency ?: 'MXN';
            if (!isset($expectedBalances[$curr])) {
                $expectedBalances[$curr] = 0;
            }

            if ($m->type === 'in') {
                $expectedBalances[$curr] += $m->amount;
            } else {
                $expectedBalances[$curr] -= $m->amount;
            }
        }

        $register->expected_balances = $expectedBalances;
        $register->expected_cash = $expectedBalances['MXN'] ?? 0;

        return response()->json($register);
    }

    /**
     * Open a new cash register.
     */
    public function open(Request $request)
    {
        // Auto-inject branch_id for non-admin users
        $user = $request->user();
        if (!$request->has('branch_id') && ($user->role !== 'admin' && strtolower($user->role) !== 'admin')) {
            $request->merge(['branch_id' => $user->branch_id]);
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'opening_amount' => 'nullable|numeric|min:0',
            'opening_balances' => 'nullable|array',
        ]);

        // Check if one is already open
        $existing = CashRegister::where('branch_id', $request->branch_id)
            ->where('status', 'open')
            ->first();

        if ($existing) {
            return response()->json(['message' => 'A shift is already open for this branch'], 400);
        }

        $register = CashRegister::create([
            'user_id' => $request->user()->id,
            'branch_id' => $request->branch_id,
            'opening_amount' => $request->opening_amount ?? 0,
            'opening_balances' => $request->opening_balances ?? ['MXN' => $request->opening_amount ?? 0, 'USD' => 0],
            'status' => 'open',
            'opened_at' => Carbon::now(),
        ]);

        return response()->json($register, 201);
    }

    /**
     * Close a cash register.
     */
    public function close(Request $request, $id)
    {
        $user = $request->user();

        // Non-admin users can only close registers from their assigned branches
        if ($user->role !== 'admin' && strtolower($user->role) !== 'admin') {
            $register = CashRegister::findOrFail($id);
            if (!$user->hasBranchAccess($register->branch_id)) {
                return response()->json(['message' => 'No tienes acceso a la sucursal de esta caja'], 403);
            }
        }

        $request->validate([
            'closing_amount' => 'nullable|numeric|min:0',
            'closing_balances' => 'nullable|array',
            'notes' => 'nullable|string'
        ]);

        $register = CashRegister::findOrFail($id);

        if ($register->status !== 'open') {
            return response()->json(['message' => 'Shift is already closed'], 400);
        }

        // Calculate expected balances
        $cashPayments = \App\Models\SalePayment::where('method', 'cash')
            ->whereHas('sale', function ($q) use ($register) {
                $q->where('cash_register_id', $register->id);
            })->get();

        $expectedBalances = $register->opening_balances ?? ['MXN' => $register->opening_amount ?? 0, 'USD' => 0];

        foreach ($cashPayments as $payment) {
            $currency = $payment->currency ?: 'MXN';
            if (!isset($expectedBalances[$currency])) {
                $expectedBalances[$currency] = 0;
            }
            $expectedBalances[$currency] += $payment->amount; // foreign currency native amount
        }

        // Include cash movements in closure
        $movements = \App\Models\CashMovement::where('cash_register_id', $register->id)->get();
        foreach ($movements as $m) {
            $curr = $m->currency ?: 'MXN';
            if (!isset($expectedBalances[$curr])) {
                $expectedBalances[$curr] = 0;
            }

            if ($m->type === 'in') {
                $expectedBalances[$curr] += $m->amount;
            } else {
                $expectedBalances[$curr] -= $m->amount;
            }
        }

        $closingBalances = $request->closing_balances ?? ['MXN' => $request->closing_amount ?? 0, 'USD' => 0];
        
        $totalExpectedMXN = $expectedBalances['MXN'] ?? 0;
        $totalClosingMXN = $closingBalances['MXN'] ?? 0;
        $diff = $totalClosingMXN - $totalExpectedMXN;

        $register->update([
            'closing_amount' => $totalClosingMXN,
            'closing_balances' => $closingBalances,
            'expected_cash' => $totalExpectedMXN,
            'expected_balances' => $expectedBalances,
            'difference' => $diff,
            'notes' => $request->notes,
            'status' => 'closed',
            'closed_at' => Carbon::now(),
        ]);

        return response()->json($register);
    }

    /**
     * Reopen a closed cash register.
     */
    public function reopen(Request $request, $id)
    {
        $register = CashRegister::findOrFail($id);

        if ($register->status !== 'closed') {
            return response()->json(['message' => 'Solo se pueden reabrir cajas que ya están cerradas.'], 400);
        }

        $register->update([
            'closing_amount' => null,
            'closing_balances' => null,
            'expected_cash' => null,
            'expected_balances' => null,
            'difference' => null,
            'status' => 'open',
            'closed_at' => null,
        ]);

        return response()->json($register);
    }
}
