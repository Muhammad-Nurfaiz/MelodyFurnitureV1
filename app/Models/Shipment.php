<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [

        'order_id',

        'courier',

        'service',

        'booking_code',

        'tracking_number',

        'label_url',

        'status',

        'metadata',

        'picked_up_at',

        'delivered_at',

    ];

    protected $casts = [
        'metadata' => 'array',
        'picked_up_at' => 'datetime',
        'delivered_at' => 'datetime',
        'last_tracking_sync_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    public function isWaitingPickup(): bool
    {
        return $this->status === 'waiting_pickup';
    }

    public function isReadyToPrint(): bool
    {
        return $this->status === 'ready_to_print';
    }

    public function isPickedUp(): bool
    {
        return $this->status === 'picked_up';
    }

    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}