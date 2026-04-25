<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\RoleMenu;
use Illuminate\Database\Seeder;

class RoleMenuSeeder extends Seeder
{
    /**
     * All menu keys available in the sidebar.
     */
    protected array $menuKeys = [
        'dashboard', 'inventory', 'pos', 'cash', 'referrals',
        'sales-history', 'reports', 'users', 'roles',
        'articles', 'products', 'branches', 'suppliers',
        'agent-types', 'exchange-rates',
    ];

    /**
     * Default visibility per role.
     */
    protected array $defaults = [
        'admin'   => true,  // admin sees everything
        'gerente' => true,  // gerente sees everything
        'cajero'  => false, // cajero sees only POS-related
    ];

    public function run(): void
    {
        $cajeroKeys = ['pos', 'cash', 'referrals'];

        foreach (Role::all() as $role) {
            $isVisible = $this->defaults[$role->name] ?? true;

            foreach ($this->menuKeys as $key) {
                RoleMenu::updateOrCreate(
                    ['role_id' => $role->id, 'menu_key' => $key],
                    ['visible' => $isVisible ? ($role->name === 'cajero' ? in_array($key, $cajeroKeys) : true) : false]
                );
            }
        }
    }
}
