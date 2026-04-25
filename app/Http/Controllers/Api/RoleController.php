<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Get all roles with their permissions.
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->get();

        return response()->json($roles);
    }

    /**
     * Create a new role.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);

        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        $data['guard_name'] = 'web';

        $role = Role::create($data);

        if (!empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        if ($request->has('menus') && is_array($request->menus)) {
            $menus = collect($request->menus)->pluck('visible', 'menu_key')->toArray();
            app(\App\Services\RoleMenuService::class)->updateMenusForRole($role, $menus);
        }

        return response()->json($role->load('permissions'), 201);
    }

    /**
     * Update a role and its permissions.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'Admin' || $role->name === 'admin') {
            return response()->json(['message' => 'El rol Admin está protegido'], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
            unset($data['permissions']);
        }

        if ($request->has('menus') && is_array($request->menus)) {
            $menus = collect($request->menus)->pluck('visible', 'menu_key')->toArray();
            app(\App\Services\RoleMenuService::class)->updateMenusForRole($role, $menus);
        }

        $role->update($data);

        // Clear cache for all users with this role
        \App\Models\Role::clearUsersCacheByRole($role);

        return response()->json($role->load('permissions'));
    }

    /**
     * Delete a role.
     */
    public function destroy($id): JsonResponse
    {
        $role = Role::findOrFail($id);

        if ($role->name === 'Admin' || $role->name === 'admin') {
            return response()->json(['message' => 'No se puede eliminar el rol Admin'], 403);
        }

        $role->delete();

        return response()->json(null, 204);
    }

    /**
     * Get available permissions for assignment.
     */
    public function availablePermissions(): JsonResponse
    {
        return response()->json(Permission::orderBy('display_name')->get());
    }
}
