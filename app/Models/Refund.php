<?php

namespace App\Models;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = [

        'order_id',

        'payment_id',

        'amount',

        'bank_name',

        'account_name',

        'account_number',

        'status',

        'processed_by',

        'notes',

        'refund_number',

        'requested_at',

        'processed_at',

        'completed_at',

    ];

    protected $casts = [

        'requested_at'=>'datetime',

        'processed_at'=>'datetime',

        'completed_at'=>'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationship
    |--------------------------------------------------------------------------
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class
        );
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(
            Payment::class
        );
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(
            Admin::class,
            'processed_by'
        );
    }
}