<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
{
    /**
     * Transform resource into an array.
     */
    public function toArray(Request $request): array
    {
        $now = now();

        $isExpired = $this->expiry_date
            ? $now->greaterThan($this->expiry_date)
            : false;

        $isStarted = $this->start_date
            ? $now->greaterThanOrEqualTo($this->start_date)
            : true;

        $isUsageLimitReached = $this->usage_limit !== null
            && $this->used_count >= $this->usage_limit;

        $isAvailable =
            $this->is_active
            && $isStarted
            && ! $isExpired
            && ! $isUsageLimitReached;

        return [

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

            'id' => $this->id,
            'code' => $this->code,

            /*
            |--------------------------------------------------------------------------
            | Discount
            |--------------------------------------------------------------------------
            */

            'discount_type' => $this->discount_type,
            'discount_value' => $this->discount_value,
            'min_order_amount' => $this->min_order_amount,
            'max_discount_amount' => $this->max_discount_amount,

            /*
            |--------------------------------------------------------------------------
            | Period
            |--------------------------------------------------------------------------
            */

            'start_date' => optional($this->start_date)?->toISOString(),
            'expiry_date' => optional($this->expiry_date)?->toISOString(),

            /*
            |--------------------------------------------------------------------------
            | Usage
            |--------------------------------------------------------------------------
            */

            'usage_limit' => $this->usage_limit,
            'used_count' => $this->used_count,

            'usage_remaining' => $this->usage_limit !== null
                ? max(
                    $this->usage_limit - $this->used_count,
                    0
                )
                : null,

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'is_active' => (bool) $this->is_active,

            'is_expired' => $isExpired,

            'is_started' => $isStarted,

            'is_usage_limit_reached' => $isUsageLimitReached,

            'is_available' => $isAvailable,

            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */

            'created_at' => optional($this->created_at)?->toISOString(),
            'updated_at' => optional($this->updated_at)?->toISOString(),
        ];
    }
}

