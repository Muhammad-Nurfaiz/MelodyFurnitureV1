<?php

namespace App\Services\Order;

use Illuminate\Support\Str;

class OrderTrackingTokenService
{
    /**
     * Generate public tracking token.
     */
    public function generate(): string
    {
        return (string) Str::ulid();
    }
}