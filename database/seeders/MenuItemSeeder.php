<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            // Principal
            ['label' => 'Dashboard',       'icon' => 'LayoutDashboard', 'route_key' => 'dashboard',     'section' => 'Principal',    'sort_order' => 1],
            ['label' => 'Inventario',      'icon' => 'Package',         'route_key' => 'inventory',     'section' => 'Principal',    'sort_order' => 2],
            // Operaciones
            ['label' => 'Punto de Venta',  'icon' => 'ShoppingCart',    'route_key' => 'pos',           'section' => 'Operaciones',  'sort_order' => 1],
            ['label' => 'Arqueo de Caja',  'icon' => 'Wallet',          'route_key' => 'cash',          'section' => 'Operaciones',  'sort_order' => 2],
            ['label' => 'Referidos',       'icon' => 'Handshake',       'route_key' => 'referrals',     'section' => 'Operaciones',  'sort_order' => 3],
            // Análisis
            ['label' => 'Historial Ventas','icon' => 'FileText',        'route_key' => 'sales-history', 'section' => 'Análisis',     'sort_order' => 1],
            ['label' => 'Reportes',        'icon' => 'BarChart3',       'route_key' => 'reports',       'section' => 'Análisis',     'sort_order' => 2],
            // Configuración
            ['label' => 'Usuarios',        'icon' => 'Users',           'route_key' => 'users',         'section' => 'Configuración','sort_order' => 1],
            ['label' => 'Roles y Permisos','icon' => 'ShieldCheck',     'route_key' => 'roles',         'section' => 'Configuración','sort_order' => 2],
            ['label' => 'Artículos',       'icon' => 'PackageCheck',    'route_key' => 'articles',      'section' => 'Configuración','sort_order' => 3],
            ['label' => 'Productos',       'icon' => 'Boxes',           'route_key' => 'products',      'section' => 'Configuración','sort_order' => 4],
            ['label' => 'Sucursales',      'icon' => 'Building2',       'route_key' => 'branches',      'section' => 'Configuración','sort_order' => 5],
            ['label' => 'Proveedores',     'icon' => 'Truck',           'route_key' => 'suppliers',     'section' => 'Configuración','sort_order' => 6],
            ['label' => 'Agencias',        'icon' => 'UsersRound',      'route_key' => 'agent-types',   'section' => 'Configuración','sort_order' => 7],
            ['label' => 'Tipos de Cambio', 'icon' => 'Landmark',        'route_key' => 'exchange-rates','section' => 'Configuración','sort_order' => 8],
        ];

        foreach ($items as $data) {
            MenuItem::updateOrCreate(['route_key' => $data['route_key']], $data);
        }
    }
}
