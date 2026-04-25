<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class RoleService
{
    /**
     * Get all roles with their permissions.
     */
    public function getAll(): Collection
    {
        return Role::with('permissions')->get();
    }

    /**
     * Get all available permissions.
     */
    public function getAllPermissions(): Collection
    {
        return Permission::all();
    }

    /**
     * Create a new role with permissions.
     */
    public function create(array $data): Role
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);

        $data['guard_name'] = $data['guard_name'] ?? 'web';

        $role = Role::create($data);

        if (!empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        return $role->load('permissions');
    }

    /**
     * Update an existing role with permissions.
     */
    public function update(Role $role, array $data): Role
    {
        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
            unset($data['permissions']);
        }

        $role->update($data);

        return $role->load('permissions');
    }

    /**
     * Delete a role.
     *
     * @throws \Exception if the role is protected
     */
    public function delete(Role $role): void
    {
        if ($role->name === 'Admin' || $role->name === 'admin') {
            throw new \Exception('Cannot delete Admin role');
        }

        $role->delete();
    }
}
