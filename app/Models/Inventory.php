<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';
    protected $fillable = ['branch_id', 'product_id', 'qty'];

    protected $casts = ['qty' => 'integer'];

    public function article()
    {
        return $this->belongsTo(Article::class, 'product_id');
    }
}
