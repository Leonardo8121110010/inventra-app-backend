<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    use HasUuids;

    protected $fillable = [
        'cash_register_id', 'branch_id', 'user_id', 'type',
        'amount', 'amount_mxn', 'currency',
        'motive_id', 'description', 'sale_id', 'referral_agent_id',
    ];

    protected $casts = [
        'amount'     => 'float',
        'amount_mxn' => 'float',
    ];

    public function cashRegister(): BelongsTo
    {
        return $this->belongsTo(CashRegister::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function motive(): BelongsTo
    {
        return $this->belongsTo(CashMovementMotive::class, 'motive_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function referralAgent(): BelongsTo
    {
        return $this->belongsTo(ReferralAgent::class, 'referral_agent_id');
    }
}
