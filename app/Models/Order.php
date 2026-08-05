<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Refund;

class Order extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [

        /*
        |--------------------------------------------------------------------------
        | Relationship
        |--------------------------------------------------------------------------
        */

        'customer_id',
        'voucher_id',

        /*
        |--------------------------------------------------------------------------
        | Identity
        |--------------------------------------------------------------------------
        */

        'order_number',
        'midtrans_order_id',
        'tracking_token',

        /*
        |--------------------------------------------------------------------------
        | Price
        |--------------------------------------------------------------------------
        */

        'total_product_price',
        'voucher_discount_amount',
        'original_shipping_fee',
        'shipping_fee',
        'total_payment',

        /*
        |--------------------------------------------------------------------------
        | Shipping
        |--------------------------------------------------------------------------
        */

        'shipping_address',
        'shipping_method',
        'courier',
        'tracking_number',
        'total_weight',

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        'status',
        'payment_status',

        /*
        |--------------------------------------------------------------------------
        | Payment
        |--------------------------------------------------------------------------
        */

        'payment_expired_at',
        'paid_at',

        /*
        |--------------------------------------------------------------------------
        | Timeline
        |--------------------------------------------------------------------------
        */

        'packed_at',
        'picked_up_at',
        'shipped_at',
        'completed_at',
        'cancelled_at',

    ];

    protected $casts = [

        /*
        |--------------------------------------------------------------------------
        | Shipping
        |--------------------------------------------------------------------------
        */

        'shipping_address' => 'array',

        /*
        |--------------------------------------------------------------------------
        | Price
        |--------------------------------------------------------------------------
        */

        'total_product_price' => 'decimal:2',
        'voucher_discount_amount' => 'decimal:2',
        'original_shipping_fee' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'total_payment' => 'decimal:2',

        /*
        |--------------------------------------------------------------------------
        | Timeline
        |--------------------------------------------------------------------------
        */

        'payment_expired_at' => 'datetime',
        'paid_at' => 'datetime',
        'packed_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'shipped_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->payment_expired_at);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function cancellationRequest(): HasOne
    {
        return $this->hasOne(OrderCancelRequest::class);
    }

    public function refund(): HasOne
    {
        return $this->hasOne(
            Refund::class
        );
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(
            Shipment::class
        );
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function canBeCancelled(): bool
    {
        return in_array(
            $this->status,
            [
                'pending',
                'paid',
                'processing',
            ],
            true
        );
    }

    public function hasShipment(): bool
    {
        return $this->shipment()->exists();
    }

    public function paymentExpired(): bool
    {
        return filled($this->payment_expired_at)
            && now()->greaterThan($this->payment_expired_at);
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isPickedUp(): bool
    {
        return $this->status === 'picked_up';
    }

    public function isShipping(): bool
    {
        return $this->status === 'shipped';
    }

    public function canTrackShipment(): bool
    {
        return in_array(
            $this->status,
            [
                'picked_up',
                'shipped',
                'completed',
            ],
            true
        );
    }

    public function canDownloadInvoice(): bool
    {
        return in_array(
            $this->status,
            [
                'paid',
                'processing',
                'picked_up',
                'shipped',
                'completed',
            ],
            true
        );
    }

    public function canRequestCancel(): bool
    {
        return in_array(
            $this->status,
            [
                'paid',
                'processing',
            ],
            true
        )
        && is_null($this->cancellationRequest);
    }

    public function canContinuePayment(): bool
    {
        return
            $this->status === 'pending'
            &&
            $this->payment_status === 'pending'
            &&
            ! $this->paymentExpired();
    }
}