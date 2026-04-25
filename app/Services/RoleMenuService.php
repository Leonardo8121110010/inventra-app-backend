<?php

namespace App\Services;

use App\Models\Role;
use App\Models\RoleMenu;
use Illuminate\Support\Collection;

class RoleMenuService
{
    /**
     * Menu keys available in the sidebar.
     */
    protected array $menuKeys = [
        'dashboard',
        'inventory',
        'pos',
        'cash',
        'referrals',
        'sales-history',
        'reports',
        'users',
        'roles',
        'articles',
        'products',
        'branches',
        'suppliers',
        'agent-types',
        'exchange-rates',
    ];

    /**
     * Get all menu entries for a role (visible and hidden).
     * If no records exist, returns all as visible by default.
     */
    public function getMenusForRole(Role $role): Collection
    {
        $stored = RoleMenu::where('role_id', $role->id)->get()->keyBy('menu_key');

        return collect($this->menuKeys)->map(fn($key) => [
            'menu_key' => $key,
            'visible'  => $stored->has($key) ? $stored->get($key)->visible : true,
        ])->values();
    }

    /**
     * Update visibility for all menus of a role.
     */
    public function updateMenusForRole(Role $role, array $menus): Collection
    {
        // $menus = ['menu_key' => bool, ...]
        RoleMenu::where('role_id', $role->id)->delete();

        foreach ($menus as $menuKey => $visible) {
            RoleMenu::create([
                'role_id'   => $role->id,
                'menu_key'  => $menuKey,
                'visible'   => (bool) $visible,
            ]);
        }

        return $this->getMenusForRole($role);
    }
}
