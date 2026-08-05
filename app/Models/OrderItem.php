<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class OrderItem extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Relations
        |--------------------------------------------------------------------------
        */

        'order_id',

        'product_id',

        /*
        |--------------------------------------------------------------------------
        | Product Snapshot
        |--------------------------------------------------------------------------
        */

        'product_name',

        'product_slug',

        'product_image',

        'product_sku',

        /*
        |--------------------------------------------------------------------------
        | Order Detail
        |--------------------------------------------------------------------------
        */

        'quantity',

        'unit_price',

        'subtotal',

    ];

    protected $casts = [

        'unit_price' => 'decimal:2',

        'subtotal'   => 'decimal:2',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}