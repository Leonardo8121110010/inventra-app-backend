<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoleMenu extends Model
{
    protected $fillable = ['role_id', 'menu_key', 'visible'];

    protected $casts = ['visible' => 'boolean'];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
