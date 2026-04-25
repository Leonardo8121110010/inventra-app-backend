<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashMovementMotive extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id', 'name', 'type', 'icon', 'applies_commission', 'sort_order', 'active',
    ];

    protected $casts = [
        'applies_commission' => 'boolean',
        'active'             => 'boolean',
        'sort_order'         => 'integer',
    ];

    public function movements()
    {
        return $this->hasMany(CashMovement::class, 'motive_id');
    }
}
