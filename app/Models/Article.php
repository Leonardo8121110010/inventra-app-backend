<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Article — the individual sellable item (formerly Product).
 * Represents a specific SKU, e.g. "Don Julio 1942 750ml".
 */
class Article extends Model
{
    protected $table = 'products';

    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'name', 'supplier_id', 'category_id', 'size_ml', 'sku', 'barcode', 'cost', 'freight', 'total_cost', 'price', 'min_stock', 'active'];

    protected $casts = [
        'cost'       => 'float',
        'freight'    => 'float',
        'total_cost' => 'float',
        'price'      => 'float',
        'min_stock'  => 'integer',
        'size_ml'    => 'integer',
        'active'     => 'boolean',
    ];

    public function product(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Product::class, 'category_id');
    }

    public function supplier(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function inventoryRows(): HasMany
    {
        return $this->hasMany(Inventory::class, 'product_id');
    }
}
