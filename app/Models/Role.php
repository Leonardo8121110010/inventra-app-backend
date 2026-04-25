<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Role extends Model
{
    protected $table = 'roles';
    protected $fillable = ['name', 'guard_name'];

    protected static function booted(): void
    {
        // Clear all users' permissions cache when role changes
        static::saved(fn($role) => self::clearAllUsersCache());
        static::deleted(fn($role) => self::clearAllUsersCache());
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions', 'role_id', 'permission_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'model_has_roles', 'role_id', 'model_id')
            ->wherePivot('model_type', User::class);
    }

    /**
     * Assign permissions to this role.
     */
    public function syncPermissions(array $permissionNames): void
    {
        $permissions = Permission::whereIn('name', $permissionNames)->pluck('id')->toArray();
        $this->permissions()->sync($permissions);

        // Clear cache for all users with this role
        self::clearUsersCacheByRole($this);
    }

    /**
     * Check if this role has a permission.
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Clear permissions cache for all users.
     */
    public static function clearAllUsersCache(): void
    {
        $userIds = User::pluck('id');
        foreach ($userIds as $id) {
            Cache::forget("user_perms_{$id}");
        }
    }

    /**
     * Clear permissions cache for users assigned to this role.
     */
    public static function clearUsersCacheByRole(Role $role): void
    {
        $userIds = User::where('role', $role->name)->pluck('id');
        foreach ($userIds as $id) {
            Cache::forget("user_perms_{$id}");
        }
    }
}
