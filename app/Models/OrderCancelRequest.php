<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderCancelRequest extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'order_id',
        'customer_id',
        'reason',
        'previous_status',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'admin_notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class
        );
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(
            Customer::class
        );
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(
            Admin::class,
            'approved_by'
        );
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(
            Admin::class,
            'rejected_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
    
    public function previousStatus(): string
    {
        return $this->previous_status;
    }
}