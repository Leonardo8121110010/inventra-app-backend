<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions (Custom system auto-clears on save/delete)

        $permissions = [
            'view-branches' => 'Ver sucursales',
            'create-branches' => 'Crear sucursales',
            'edit-branches' => 'Editar sucursales',
            'delete-branches' => 'Eliminar sucursales',
            
            'view-products' => 'Ver productos',
            'create-products' => 'Crear productos',
            'edit-products' => 'Editar productos',
            'delete-products' => 'Eliminar productos',
            
            'view-suppliers' => 'Ver proveedores',
            'create-suppliers' => 'Crear proveedores',
            'edit-suppliers' => 'Editar proveedores',
            'delete-suppliers' => 'Eliminar proveedores',
            
            'view-articles' => 'Ver artículos',
            'create-articles' => 'Crear artículos',
            'edit-articles' => 'Editar artículos',
            'delete-articles' => 'Eliminar artículos',
            
            'view-users' => 'Ver usuarios',
            'create-users' => 'Crear usuarios',
            'edit-users' => 'Editar usuarios',
            'delete-users' => 'Eliminar usuarios',
            
            'view-inventory' => 'Ver inventario',
            'adjust-inventory' => 'Ajustar inventario',
            
            'view-sales' => 'Ver ventas',
            'create-sales' => 'Crear ventas',
            
            'view-movements' => 'Ver movimientos de caja',
            'create-movements' => 'Crear movimientos de caja',
            
            'view-agent-types' => 'Ver tipos de agente (CRM)',
            'create-agent-types' => 'Crear tipos de agente (CRM)',
            'edit-agent-types' => 'Editar tipos de agente (CRM)',
            'delete-agent-types' => 'Eliminar tipos de agente (CRM)',
            
            'view-referral-agents' => 'Ver agentes (CRM)',
            'create-referral-agents' => 'Crear agentes (CRM)',
            'edit-referral-agents' => 'Editar agentes (CRM)',
            
            'view-commission-rules' => 'Ver reglas de comisión (CRM)',
            'create-commission-rules' => 'Crear reglas de comisión (CRM)',
            'edit-commission-rules' => 'Editar reglas de comisión (CRM)',
            
            'view-commissions' => 'Ver comisiones',
            'pay-commissions' => 'Pagar comisiones',
            
            'view-cash-registers' => 'Ver cajas',
            'manage-cash-registers' => 'Gestionar cajas',
            
            'view-exchange-rates' => 'Ver tipo de cambio',
            'manage-exchange-rates' => 'Gestionar tipo de cambio',
            
            'manage-roles' => 'Gestionar roles',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['display_name' => $displayName]
            );
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions(Permission::all()->pluck('name')->toArray());

        Role::firstOrCreate(['name' => 'gerente', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cajero',  'guard_name' => 'web']);

        // No need to call assignRole, the custom system uses the 'role' column on the User model
        // which is already seeded in UserSeeder.
    }
}
