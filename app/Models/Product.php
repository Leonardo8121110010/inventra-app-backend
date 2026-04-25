<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Product — the classification/grouping entity (formerly Category).
 * Represents a product line or family, e.g. "Red Wine", "Tequila".
 */
class Product extends Model
{
    protected $table = 'categories';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'name', 'icon', 'color', 'parent_id'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'parent_id');
    }

    public function subproducts(): HasMany
    {
        return $this->hasMany(Product::class, 'parent_id');
    }
}
