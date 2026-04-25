<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch_id',
        'avatar',
        'active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'active'            => 'boolean',
        ];
    }


    /**
     * Get all branches this user has access to.
     */
    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_user', 'user_id', 'branch_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /**
     * Check if user has access to a specific branch.
     */
    public function hasBranchAccess(string $branchId): bool
    {
        if ($this->role === 'admin' || strtolower($this->role) === 'admin') {
            return true;
        }
        return $this->branches()->where('branch_id', $branchId)->exists();
    }

    /**
     * Get permission keys for the user (with cache).
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $permissions = $this->getPermissions();

        return in_array($permissionName, $permissions);
    }

    /**
     * Get user's permissions through their role (cached for 2 hours).
     * Cache is automatically invalidated when roles/permissions change.
     */
    public function getPermissions(): array
    {
        return Cache::remember("user_perms_{$this->id}", now()->addHours(2), function () {
            $role = $this->getRole();
            if (!$role) {
                return [];
            }
            return $role->permissions()->pluck('name')->toArray();
        });
    }

    /**
     * Clear the permissions cache for this user.
     */
    public function clearPermissionsCache(): void
    {
        Cache::forget("user_perms_{$this->id}");
    }

    /**
     * Check if user is the super admin role.
     */
    public function isSuperAdmin(): bool
    {
        $adminRole = config('permissions.super_admin_role', 'admin');
        return $this->role === $adminRole || strtolower($this->role) === strtolower($adminRole);
    }

    /**
     * Get user's role model.
     */
    public function getRole(): ?Role
    {
        return Role::where('name', $this->role)->first();
    }
}
