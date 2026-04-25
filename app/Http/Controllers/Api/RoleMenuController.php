<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Services\RoleMenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleMenuController extends Controller
{
    protected RoleMenuService $roleMenuService;

    public function __construct(RoleMenuService $roleMenuService)
    {
        $this->roleMenuService = $roleMenuService;
    }

    /**
     * Get all menu items with visibility for a role.
     */
    public function index(int $roleId): JsonResponse
    {
        $role = Role::findOrFail($roleId);

        return response()->json([
            'menus'  => $this->roleMenuService->getMenusForRole($role),
            'role'   => $role->only(['id', 'name']),
        ]);
    }

    /**
     * Update menu visibility for a role.
     */
    public function update(Request $request, int $roleId): JsonResponse
    {
        $role = Role::findOrFail($roleId);

        $data = $request->validate([
            'menus'   => 'required|array',
            'menus.*.menu_key' => 'required|string',
            'menus.*.visible'  => 'required|boolean',
        ]);

        // Flatten to key-value map
        $menus = collect($data['menus'])->pluck('visible', 'menu_key')->toArray();

        $updated = $this->roleMenuService->updateMenusForRole($role, $menus);

        return response()->json(['menus' => $updated]);
    }
}
