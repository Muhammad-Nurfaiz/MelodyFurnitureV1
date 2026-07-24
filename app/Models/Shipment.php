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

    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class
        );
    }
}