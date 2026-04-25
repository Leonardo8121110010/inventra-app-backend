<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBranchAccess
{
    /**
     * Handle an incoming request.
     *
     * Ensures the authenticated user has access to the branch
     * specified in the request (branch_id parameter or payload).
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
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

        // Determine target branch from route param, query string, or JSON body
        $targetBranch = $request->route('branch')
            ?? $request->route('branch_id')
            ?? $request->input('branch_id')
            ?? $request->input('branch');

        if (!$targetBranch) {
            return response()->json(['message' => 'branch_id requerido'], 400);
        }

        if (!$user->hasBranchAccess($targetBranch)) {
            return response()->json([
                'message' => 'No tienes acceso a esta sucursal'
            ], 403);
        }

        return $next($request);
    }
}
