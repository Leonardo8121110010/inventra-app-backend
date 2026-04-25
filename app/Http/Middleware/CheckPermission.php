<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if (!$user->active) {
            return response()->json(['message' => 'Usuario desactivado'], 403);
        }

        // Super admin bypass (configurable)
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Multiple permissions using |
        $requiredPermissions = explode('|', $permission);
        $hasPermission = false;
        $missingPermissionModel = null;

        foreach ($requiredPermissions as $reqPerm) {
            $permissionModel = Permission::firstOrCreate(
                ['name' => $reqPerm],
                ['display_name' => $this->humanizePermissionName($reqPerm), 'guard_name' => 'web']
            );

            if ($user->hasPermission($reqPerm)) {
                $hasPermission = true;
                break; // One is enough
            } else {
                $missingPermissionModel = $permissionModel;
            }
        }

        if (!$hasPermission) {
            return response()->json([
                'message' => "No tiene el permiso requerido: {$missingPermissionModel->display_name}"
            ], 403);
        }

        return $next($request);
    }

    private function humanizePermissionName(string $name): string
    {
        $parts = explode('-', $name);
        $action = $parts[0] ?? '';
        $resource = $parts[1] ?? '';

        $actionMap = [
            'view' => 'Ver', 'create' => 'Crear', 'edit' => 'Editar',
            'delete' => 'Eliminar', 'manage' => 'Gestionar', 'adjust' => 'Ajustar', 'pay' => 'Pagar',
        ];

        $resourceMap = [
            'branches' => 'Sucursales', 'products' => 'Productos', 'suppliers' => 'Proveedores',
            'articles' => 'Artículos', 'users' => 'Usuarios', 'inventory' => 'Inventario',
            'sales' => 'Ventas', 'movements' => 'Movimientos', 'agent-types' => 'Tipos de Agente',
            'referral-agents' => 'Agentes Referidos', 'commission-rules' => 'Reglas de Comisión',
            'commissions' => 'Comisiones', 'cash-registers' => 'Cajas',
            'exchange-rates' => 'Tipos de Cambio', 'roles' => 'Roles',
        ];

        $actionLabel = $actionMap[$action] ?? ucfirst($action);
        $resourceLabel = $resourceMap[$resource] ?? ucfirst(str_replace('-', ' ', $resource));

        return "{$actionLabel} {$resourceLabel}";
    }
}
