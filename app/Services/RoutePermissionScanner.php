<?php

namespace App\Services;

use App\Models\Permission;
use Illuminate\Support\Facades\Route;

class RoutePermissionScanner
{
    /**
     * Scan all API routes for permission middleware and auto-register permissions.
     */
    public function scanAndRegister(): array
    {
        $registered = [];

        foreach (Route::getRoutes() as $route) {
            $permission = $this->extractPermissionFromRoute($route);
            if ($permission && !in_array($permission, $registered)) {
                $perm = Permission::firstOrCreate(
                    ['name' => $permission],
                    ['display_name' => $this->humanizePermissionName($permission), 'guard_name' => 'web']
                );
                $registered[] = $permission;
            }
        }

        return $registered;
    }

    /**
     * Get all permissions used in route middleware.
     */
    public function getRoutePermissions(): array
    {
        $permissions = [];

        foreach (Route::getRoutes() as $route) {
            $perm = $this->extractPermissionFromRoute($route);
            if ($perm && !in_array($perm, $permissions)) {
                $permissions[] = $perm;
            }
        }

        return $permissions;
    }

    /**
     * Extract permission name from route middleware.
     */
    private function extractPermissionFromRoute($route): ?string
    {
        $middlewares = $route->middleware();

        foreach ($middlewares as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'permission:')) {
                return str_replace('permission:', '', $middleware);
            }
        }

        return null;
    }

    /**
     * Convert 'view-branches' to 'Ver Sucursales'.
     */
    public function humanizePermissionName(string $name): string
    {
        $parts = explode('-', $name);
        $action = $parts[0] ?? '';
        $resource = $parts[1] ?? '';

        $actionMap = [
            'view'     => 'Ver',
            'create'   => 'Crear',
            'edit'     => 'Editar',
            'delete'   => 'Eliminar',
            'manage'   => 'Gestionar',
            'adjust'   => 'Ajustar',
            'pay'      => 'Pagar',
            'cancel'   => 'Cancelar',
            'approve'  => 'Aprobar',
            'print'    => 'Imprimir',
            'report'   => 'Reportar',
            'sync'     => 'Sincronizar',
            'assign'   => 'Asignar',
            'discover' => 'Descubrir',
            'list'     => 'Listar',
            'show'     => 'Mostrar',
            'store'    => 'Guardar',
            'update'   => 'Actualizar',
        ];

        $resourceMap = [
            'branches'         => 'Sucursales',
            'products'         => 'Productos',
            'suppliers'        => 'Proveedores',
            'articles'         => 'Artículos',
            'users'            => 'Usuarios',
            'inventory'        => 'Inventario',
            'sales'            => 'Ventas',
            'movements'        => 'Movimientos',
            'agent-types'      => 'Tipos de Agente',
            'referral-agents'  => 'Agentes Referidos',
            'commission-rules' => 'Reglas de Comisión',
            'commissions'      => 'Comisiones',
            'cash-registers'   => 'Cajas',
            'exchange-rates'   => 'Tipos de Cambio',
            'roles'            => 'Roles',
            'brands'           => 'Marcas',
            'categories'       => 'Categorías',
            'menu-items'       => 'Menús',
            'permissions'      => 'Permisos',
            'pos'              => 'Punto de Venta',
            'reports'          => 'Reportes',
            'dashboard'        => 'Panel de Control',
            'settings'         => 'Configuraciones',
        ];

        $actionLabel = $actionMap[$action] ?? ucfirst($action);
        $resourceLabel = $resourceMap[$resource] ?? ucfirst(str_replace('-', ' ', $resource));

        return "{$actionLabel} {$resourceLabel}";
    }
}
