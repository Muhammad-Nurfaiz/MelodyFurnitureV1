<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'original_price',
        'discount_price',
        'discount_percentage',
        'media_id',
        'ready_stock',
        'locked_stock',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'discount_percentage' => 'integer',
        'ready_stock' => 'integer',
        'locked_stock' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function media()
    {
        return $this->belongsTo(ProductMedia::class, 'media_id');
    }
}