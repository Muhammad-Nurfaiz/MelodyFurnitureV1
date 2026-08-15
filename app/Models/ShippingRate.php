<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MadeByClowd\Nusantara\Models\Regency;

class ShippingRate extends Model
{
    use HasUuids;

    protected $fillable = [
        'courier_id',
        'regency_id',
        'rate_type',
        'price_per_kg',
        'first_price',
        'additional_price_per_kg',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_per_kg' => 'decimal:2',
            'first_price' => 'decimal:2',
            'additional_price_per_kg' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Courier belonging to this rate.
     */
    public function courier(): BelongsTo
    {
        return $this->belongsTo(
            ShippingCourier::class,
            'courier_id'
        );
    }

    /**
     * Destination regency / city.
     */
    public function regency(): BelongsTo
    {
        return $this->belongsTo(
            Regency::class,
            'regency_id',
            'id'
        );
    }
}