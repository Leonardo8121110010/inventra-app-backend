<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'agent_id', 'sale_id', 'sale_amount', 'commission_amount', 'date', 'status', 'rule_id'];

    protected $casts = [
        'sale_amount'       => 'float',
        'commission_amount' => 'float',
        'date'              => 'datetime',
    ];

    public function agent()
    {
        return $this->belongsTo(ReferralAgent::class, 'agent_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function rule()
    {
        return $this->belongsTo(CommissionRule::class, 'rule_id');
    }
}
