<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'branch_id',
        'opening_amount',
        'closing_amount',
        'status',
        'opened_at',
        'closed_at',
        'expected_cash',
        'difference',
        'notes',
        'opening_balances',
        'closing_balances',
        'expected_balances',
    ];

    protected $casts = [
        'opening_balances'  => 'array',
        'closing_balances'  => 'array',
        'expected_balances' => 'array',
        'opened_at'         => 'datetime',
        'closed_at'         => 'datetime',
    ];

    /**
     * Get the user that opened the cash register.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the branch where the cash register is opened.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Get the sales associated with this cash register session.
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
