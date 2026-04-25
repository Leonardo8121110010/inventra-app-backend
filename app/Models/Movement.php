<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movement extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'type', 'product_id', 'branch_id', 'qty', 'date', 'user', 'notes'];

    protected $casts = [
        'qty'  => 'integer',
        'date' => 'datetime',
    ];
}
