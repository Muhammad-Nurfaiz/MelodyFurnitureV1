<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Order extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'voucher_id',
        'customer_id',
        'order_number',
        'total_product_price',
        'voucher_discount_amount',
        'original_shipping_fee',
        'shipping_fee',
        'total_payment',
        'shipping_method',
        'shipping_address',
        'status',
        'payment_expired_at'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}