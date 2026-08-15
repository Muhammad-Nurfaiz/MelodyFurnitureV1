<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingCourier extends Model
{
    use HasUuids;

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Shipping rates belonging to this courier.
     */
    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'courier_id');
    }
}