<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalePayment extends Model
{
    protected $fillable = [
        'id',
        'sale_id',
        'method',
        'currency',
        'amount',
        'amount_mxn',
        'exchange_rate',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
