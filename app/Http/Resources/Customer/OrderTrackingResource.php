<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Customer\CancellationRequestResource;
use App\Http\Resources\Customer\OrderStatusHistoryResource;
use App\Http\Resources\Customer\OrderItemResource;

class OrderTrackingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {
        return [
            /*
            |--------------------------------------------------------------------------
            | Order
            |--------------------------------------------------------------------------
            */

            'order_number' => $this->order_number,
            'tracking_token' => $this->tracking_token,
            'status' => $this->status,
            'payment_status' => $this->payment_status,

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            'customer' => [
                'name' => $this->customer?->name,
                'phone' => $this->customer?->phone,
                'email' => $this->customer?->email,
            ],

            /*
            |--------------------------------------------------------------------------
            | Shipping
            |--------------------------------------------------------------------------
            */

            'shipping' => [
                'courier' => $this->courier,
                'service' => $this->shipping_service,
                'tracking_number' => $this->tracking_number,
                'tracking_code' => $this->tracking_code,
                'address' => $this->shipping_address,
            ],

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'payment' => [
                'method' => $this->payment?->payment_method,
                'status' => $this->payment_status,
                'snap_token' => $this->payment?->snap_token,
                'redirect_url' => $this->payment?->redirect_url,
                'expired_at' => $this->payment_expired_at,
                'paid_at' => $this->paid_at,
            ],

            /*
            |--------------------------------------------------------------------------
            | Total
            |--------------------------------------------------------------------------
            */

            'summary' => [
                'subtotal' => $this->subtotal,
                'voucher_discount' => $this->voucher_discount,
                'shipping_fee' => $this->shipping_fee,
                'total_payment' => $this->total_payment,
            ],

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => $this->items->map(function ($item) {
                return [
                    'product_name' => $item->product_name,
                    'thumbnail' => $item->product_thumbnail,
                    'qty' => $item->quantity,
                    'price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ];
            }),

            /*
            |--------------------------------------------------------------------------
            | Cancellation
            |--------------------------------------------------------------------------
            */

            'cancellation_request' =>
                new CancellationRequestResource(
                    $this->whenLoaded(
                        'cancellationRequest'
                    )
                ),

            /*
            |--------------------------------------------------------------------------
            | Timeline
            |--------------------------------------------------------------------------
            */

            'items' =>
                OrderItemResource::collection(
                    $this->whenLoaded(
                        'items'
                    )
                ),

            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}