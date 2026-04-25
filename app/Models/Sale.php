<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'branch_id', 'user_id', 'date', 'total', 'total_mxn',
        'subtotal', 'discount', 'payment', 'currency', 'referral_agent_id', 'cash_register_id',
    ];

    protected $casts = [
        'total'      => 'float',
        'total_mxn'  => 'float',
        'subtotal'   => 'float',
        'discount'   => 'float',
        'date'       => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id');
    }

    public function referralAgent()
    {
        return $this->belongsTo(ReferralAgent::class, 'referral_agent_id');
    }

    public function referralAgents()
    {
        return $this->belongsToMany(ReferralAgent::class, 'sale_referral_agents', 'sale_id', 'referral_agent_id')
            ->withPivot('agent_type_id')
            ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SalePayment::class, 'sale_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'sale_id');
    }
}
