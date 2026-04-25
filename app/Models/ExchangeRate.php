<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $primaryKey = 'currency';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'currency',
        'rate',
        'is_live',
    ];
}
