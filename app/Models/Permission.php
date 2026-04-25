<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Cache;

class Permission extends Model
{
    protected $table = 'permissions';
    protected $fillable = ['name', 'display_name', 'guard_name'];

    protected static function booted(): void
    {
        // Clear all users' permissions cache when permission changes
        static::saved(fn() => Role::clearAllUsersCache());
        static::deleted(fn() => Role::clearAllUsersCache());
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions', 'permission_id', 'role_id');
    }
}
