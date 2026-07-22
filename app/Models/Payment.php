<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Payment extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [

        'order_id',

        'transaction_id',

        'snap_token',

        'payment_type',

        'transaction_status',

        'fraud_status',

        'gross_amount',

        'bank',

        'va_number',

        'expired_at',

        'paid_at',

        'raw_response',

    ];

    protected $casts = [

        'gross_amount' => 'decimal:2',

        'expired_at' => 'datetime',

        'paid_at' => 'datetime',

        'raw_response' => 'array',

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

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->transaction_status === 'pending';
    }

    public function isPaid(): bool
    {
        return $this->transaction_status === 'settlement';
    }

    public function isExpired(): bool
    {
        return $this->transaction_status === 'expire';
    }

    public function isCancelled(): bool
    {
        return in_array($this->transaction_status, [
            'cancel',
            'deny',
        ]);
    }
}