<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'transaction_id' => $this->transaction_id,
            'transaction_status' => $this->transaction_status,
            'payment_type' => $this->payment_type,
            'bank' => $this->bank,
            'va_number' => $this->va_number,
            'snap_token' => $this->snap_token,
            'expired_at' => optional($this->expired_at) ?->toISOString(),
            'paid_at' => optional($this->paid_at) ?->toISOString(),
        ];
    }
}