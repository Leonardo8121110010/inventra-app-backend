<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'product_id', 'qty', 'price', 'price_in_currency', 'currency'];

    protected $casts = [
        'qty'               => 'integer',
        'price'             => 'float',
        'price_in_currency' => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function article()
    {
        return $this->belongsTo(Article::class, 'product_id');
    }
}
