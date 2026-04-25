<?php

namespace App\Http\Middleware;

use App\Models\Sale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBranchAccessSale
{
    /**
     * Handle an incoming request.
     *
     * Finds the sale by ID, extracts its branch_id, and validates
     * that the authenticated user has access to that branch.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Admin bypass
        if ($user->role === 'admin' || strtolower($user->role) === 'admin') {
            return $next($request);
        }

        $saleId = $request->route('sale') ?? $request->route('id');

        if (!$saleId) {
            return response()->json(['message' => 'Sale ID requerido'], 400);
        }

        $sale = Sale::find($saleId);

        if (!$sale) {
            return response()->json(['message' => 'Venta no encontrada'], 404);
        }

        if (!$user->hasBranchAccess($sale->branch_id)) {
            return response()->json([
                'message' => 'No tienes acceso a la sucursal de esta venta'
            ], 403);
        }

        return $next($request);
    }
}
