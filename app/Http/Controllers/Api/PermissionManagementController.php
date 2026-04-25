<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Services\RoutePermissionScanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionManagementController extends Controller
{
    protected RoutePermissionScanner $scanner;

    public function __construct(RoutePermissionScanner $scanner)
    {
        $this->scanner = $scanner;
    }

    /**
     * Get all permissions (registered + discovered from routes).
     */
    public function index(): JsonResponse
    {
        // Auto-register any new permissions found in routes
        $this->scanner->scanAndRegister();

        $permissions = Permission::orderBy('name')->get();

        return response()->json($permissions);
    }

    /**
     * Update a permission's display name.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'display_name' => 'required|string|max:255',
        ]);

        $permission = Permission::findOrFail($id);
        $permission->update($data);

        // Clear all users cache when permission display name changes
        \App\Models\Role::clearAllUsersCache();

        return response()->json($permission);
    }

    /**
     * Get permissions discovered from routes that are NOT yet registered.
     */
    public function discover(): JsonResponse
    {
        $routePermissions = $this->scanner->getRoutePermissions();
        $registeredPermissions = Permission::pluck('name')->toArray();
        $missing = array_diff($routePermissions, $registeredPermissions);

        return response()->json([
            'route_permissions' => $routePermissions,
            'registered' => $registeredPermissions,
            'missing' => array_values($missing),
        ]);
    }
}
