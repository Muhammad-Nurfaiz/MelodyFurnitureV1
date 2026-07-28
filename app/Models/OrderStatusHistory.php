<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusHistory extends Model
{
    use HasUuids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'order_id',
        'status',
        'description',
        'admin_id',
        'actor',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(
            Admin::class,
            'admin_id'
        );
    }

    public function isAutomatic(): bool
    {
        return is_null($this->admin_id);
    }

    public function isManual(): bool
    {
        return ! is_null($this->admin_id);
    }
}