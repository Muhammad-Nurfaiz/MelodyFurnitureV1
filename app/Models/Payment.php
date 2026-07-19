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
        'target_bank',
        'va_number',
        'expiry_time',
        'status'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}