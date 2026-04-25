<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
        });

        // Auto-populate display_name from existing permission names
        $permissions = \Illuminate\Support\Facades\DB::table('permissions')->get();
        foreach ($permissions as $perm) {
            $displayName = $this->humanizePermissionName($perm->name);
            \Illuminate\Support\Facades\DB::table('permissions')
                ->where('id', $perm->id)
                ->update(['display_name' => $displayName]);
        }
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('display_name');
        });
    }

    private function humanizePermissionName(string $name): string
    {
        // Convert 'view-branches' → 'Ver Sucursales'
        $parts = explode('-', $name);
        $action = $parts[0] ?? '';
        $resource = $parts[1] ?? '';

        $actionMap = [
            'view' => 'Ver',
            'create' => 'Crear',
            'edit' => 'Editar',
            'delete' => 'Eliminar',
            'manage' => 'Gestionar',
            'adjust' => 'Ajustar',
            'pay' => 'Pagar',
        ];

        $resourceMap = [
            'branches' => 'Sucursales',
            'products' => 'Productos',
            'suppliers' => 'Proveedores',
            'articles' => 'Artículos',
            'users' => 'Usuarios',
            'inventory' => 'Inventario',
            'sales' => 'Ventas',
            'movements' => 'Movimientos',
            'agent-types' => 'Tipos de Agente',
            'referral-agents' => 'Agentes Referidos',
            'commission-rules' => 'Reglas de Comisión',
            'commissions' => 'Comisiones',
            'cash-registers' => 'Cajas',
            'exchange-rates' => 'Tipos de Cambio',
            'roles' => 'Roles',
        ];

        $actionLabel = $actionMap[$action] ?? ucfirst($action);
        $resourceLabel = $resourceMap[$resource] ?? ucfirst(str_replace('-', ' ', $resource));

        return "{$actionLabel} {$resourceLabel}";
    }
};
