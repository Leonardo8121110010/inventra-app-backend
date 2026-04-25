<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiPermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user()) {
            abort(401, 'No autenticado.');
        }

        if (!$request->user()->can($permission)) {
            abort(403, 'No tiene permisos para realizar esta acción.');
        }

        return $next($request);
    }
}
