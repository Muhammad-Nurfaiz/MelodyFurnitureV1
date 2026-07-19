<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Voucher extends Model
{
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_discount_amount',
        'start_date',
        'expiry_date',
        'usage_limit',
        'used_count',
        'is_active'
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}