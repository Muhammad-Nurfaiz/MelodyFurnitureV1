<?php

namespace App\Models;

use App\Models\Order;
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
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',

        'start_date' => 'datetime',
        'expiry_date' => 'datetime',

        'usage_limit' => 'integer',
        'used_count' => 'integer',

        'is_active' => 'boolean',
    ];

    protected $appends = [
        'is_started',
        'is_expired',
        'is_usage_limit_reached',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Apakah voucher sudah memasuki periode berlaku?
     *
     * Jika start_date NULL, voucher dianggap
     * langsung mulai berlaku.
     */
    public function getIsStartedAttribute(): bool
    {
        if (! $this->start_date) {
            return true;
        }

        return now()->greaterThanOrEqualTo($this->start_date);
    }

    /**
     * Apakah voucher sudah expired?
     */
    public function getIsExpiredAttribute(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return now()->greaterThan($this->expiry_date);
    }

    /**
     * Apakah batas penggunaan voucher sudah tercapai?
     *
     * usage_limit NULL = tidak terbatas.
     */
    public function getIsUsageLimitReachedAttribute(): bool
    {
        if ($this->usage_limit === null) {
            return false;
        }

        return $this->used_count >= $this->usage_limit;
    }
}