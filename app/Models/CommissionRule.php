<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionRule extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'agent_id', 'agent_type_id', 'product_id', 'commission_type', 'value', 'trigger', 'volume_threshold', 'active', 'period'];

    protected $casts = [
        'value'            => 'float',
        'volume_threshold' => 'integer',
        'active'           => 'boolean',
    ];
}
