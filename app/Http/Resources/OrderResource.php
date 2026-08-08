<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform resource.
     */
    public function toArray(Request $request): array
    {
        return [

            'id' => $this->id,
            'order_number' => $this->order_number,
            'tracking_token' => $this->tracking_token,

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            'status' => $this->status,
            'payment_status' => $this->payment_status,

            /*
            |--------------------------------------------------------------------------
            | Customer Snapshot
            |--------------------------------------------------------------------------
            */

            'customer' => [
                'name' => $this->customer_name,
                'email' => $this->customer_email,
                'phone' => $this->customer_phone,
            ],

            /*
            |--------------------------------------------------------------------------
            | Price
            |--------------------------------------------------------------------------
            */

            'subtotal' => $this->total_product_price,
            'voucher_discount' => $this->voucher_discount_amount,
            'shipping_fee' => $this->shipping_fee,
            'total_payment' => $this->total_payment,

            /*
            |--------------------------------------------------------------------------
            | Shipping
            |--------------------------------------------------------------------------
            */

            'courier' => $this->courier,
            'shipping_method' => $this->shipping_method,
            'shipping_address' => $this->shipping_address,

            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */

            'created_at' => optional($this->created_at)?->toISOString(),
            'payment_expired_at' => optional($this->payment_expired_at)?->toISOString(),

            /*
            |--------------------------------------------------------------------------
            | Relations
            |--------------------------------------------------------------------------
            */

            'payment' => PaymentResource::make(
                $this->whenLoaded('payment')
            ),

            'items' => OrderItemResource::collection(
                $this->whenLoaded('items')
            ),
        ];
    }
}