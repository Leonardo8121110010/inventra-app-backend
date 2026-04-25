<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Movement;
use Illuminate\Http\Request;

class MovementController extends Controller
{
    public function index(Request $request)
    {
        $query = Movement::query()->orderByDesc('date');
        $user = $request->user();

        // Non-admin users can only see movements from their assigned branches
        if ($user->role !== 'admin' && strtolower($user->role) !== 'admin') {
            $branchIds = $user->branches()->pluck('branch_id')->toArray();
            if (empty($branchIds)) {
                $branchIds = [$user->branch_id];
            }
            $query->whereIn('branch_id', $branchIds);
        }

        if ($request->filled('branch_id'))  $query->where('branch_id',  $request->branch_id);
        if ($request->filled('product_id')) $query->where('product_id', $request->product_id);
        if ($request->filled('type'))       $query->where('type',       $request->type);

        return response()->json($query->limit(500)->get());
    }

    public function store(Request $request)
    {
        // Auto-inject branch_id for non-admin users
        $user = $request->user();
        if (!$request->has('branch_id') && ($user->role !== 'admin' && strtolower($user->role) !== 'admin')) {
            $request->merge(['branch_id' => $user->branch_id]);
        }

        $data = $request->validate([
            'type'       => 'required|in:ENTRY,EXIT,ADJUSTMENT,TRANSFER_IN,TRANSFER_OUT',
            'product_id' => 'required|string|exists:products,id',
            'branch_id'  => 'required|string|exists:branches,id',
            'qty'        => 'required|integer',
            'date'       => 'nullable|date',
            'notes'      => 'nullable|string',
        ]);

        // Security Validation: Enforce sign mathematically
        if (in_array($data['type'], ['ENTRY', 'TRANSFER_IN']) && $data['qty'] <= 0) {
            return response()->json(['message' => 'La cantidad para Entradas debe ser positiva.'], 422);
        }
        if (in_array($data['type'], ['EXIT', 'TRANSFER_OUT']) && $data['qty'] >= 0) {
            return response()->json(['message' => 'La cantidad para Salidas debe ser negativa.'], 422);
        }

        $data['id']   = 'm' . time() . rand(100, 999);
        $data['user'] = $request->user()->name;
        $data['date'] = now();

        return \DB::transaction(function () use ($data, $request) {
            // Adjust inventory
            $inventory = \App\Models\Inventory::where('branch_id', $data['branch_id'])
                ->where('product_id', $data['product_id'])
                ->lockForUpdate()
                ->first();

            if (!$inventory) {
                $inventory = \App\Models\Inventory::create([
                    'branch_id' => $data['branch_id'],
                    'product_id' => $data['product_id'],
                    'qty' => 0
                ]);
            }

            $newQty = $inventory->qty + $data['qty'];

            if ($newQty < 0) {
                return response()->json([
                    'message' => 'Existencias insuficientes para realizar esta operación.',
                    'current_stock' => $inventory->qty,
                    'requested_change' => $data['qty']
                ], 422);
            }

            $inventory->qty = $newQty;
            $inventory->save();

            $movement = Movement::create($data);
            return response()->json($movement, 201);
        });
    }

    public function transfer(Request $request)
    {
        $data = $request->validate([
            'product_id'  => 'required|string|exists:products,id',
            'from_branch' => 'required|string|exists:branches,id',
            'to_branch'   => 'required|string|exists:branches,id|different:from_branch',
            'qty'         => 'required|integer|min:1',
            'notes'       => 'nullable|string',
        ]);

        return \DB::transaction(function () use ($data, $request) {
            // 1. Deduct from origin
            $originInventory = \App\Models\Inventory::where('branch_id', $data['from_branch'])
                ->where('product_id', $data['product_id'])
                ->lockForUpdate()
                ->first();

            if (!$originInventory || $originInventory->qty < $data['qty']) {
                $available = $originInventory ? $originInventory->qty : 0;
                return response()->json([
                    'message' => 'Existencias insuficientes en la sucursal de origen.',
                    'current_stock' => $available,
                ], 422);
            }

            $originInventory->qty -= $data['qty'];
            $originInventory->save();

            // 2. Add to destination
            $destInventory = \App\Models\Inventory::firstOrCreate(
                ['branch_id' => $data['to_branch'], 'product_id' => $data['product_id']],
                ['qty' => 0]
            );
            // lock dest to be safe
            $destInventory = \App\Models\Inventory::where('id', $destInventory->id)->lockForUpdate()->first();
            $destInventory->qty += $data['qty'];
            $destInventory->save();

            // 3. Record movements
            $baseId = time() . rand(100, 999);
            $user = $request->user()->name;
            $now = now();
            $notes = $data['notes'] ?? 'Traspaso';

            // From branch name (optional, for notes)
            $fromName = \App\Models\Branch::find($data['from_branch'])->name ?? $data['from_branch'];
            $toName = \App\Models\Branch::find($data['to_branch'])->name ?? $data['to_branch'];

            $out = Movement::create([
                'id' => 'm_out_' . $baseId,
                'type' => 'TRANSFER_OUT',
                'product_id' => $data['product_id'],
                'branch_id' => $data['from_branch'],
                'qty' => -$data['qty'],
                'date' => $now,
                'user' => $user,
                'notes' => $data['notes'] ? $data['notes'] : "Traspaso -> $toName",
            ]);

            $in = Movement::create([
                'id' => 'm_in_' . $baseId,
                'type' => 'TRANSFER_IN',
                'product_id' => $data['product_id'],
                'branch_id' => $data['to_branch'],
                'qty' => $data['qty'],
                'date' => $now,
                'user' => $user,
                'notes' => $data['notes'] ? $data['notes'] : "Traspaso <- $fromName",
            ]);

            return response()->json([
                'message' => 'Traspaso completado exitosamente',
                'out' => $out,
                'in' => $in
            ], 201);
        });
    }
}
