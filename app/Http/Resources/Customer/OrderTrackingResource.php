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
            'status_label' => $this->statusLabel(),
            'payment_status' => $this->payment_status,

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            'customer' => [
                'name' => $this->customer?->name ?? '',
                'phone' => $this->customer?->phone ?? '',
                'email' => $this->customer?->email ?? '',
            ],

            /*
            |--------------------------------------------------------------------------
            | Shipping
            |--------------------------------------------------------------------------
            */

            'shipping' => [
                'courier' => $this->courier,
                'method' => $this->shipping_method,
                'tracking_number' => $this->tracking_number,
                'tracking_url' => null,
                'address' => $this->shipping_address,
                'packed_at' => $this->packed_at?->toIso8601String(),
                'picked_up_at' => $this->picked_up_at?->toIso8601String(),
                'shipped_at' => $this->shipped_at?->toIso8601String(),
                'completed_at' => $this->completed_at?->toIso8601String(),
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
                'expired_at' => $this->payment_expired_at?->toIso8601String(),
                'paid_at' => $this->paid_at?->toIso8601String(),
                'is_expired' => filled($this->payment_expired_at) && now()->greaterThan($this->payment_expired_at),
            ],

            /*
            |--------------------------------------------------------------------------
            | Summary
            |--------------------------------------------------------------------------
            */

            'summary' => [
                'subtotal' => $this->total_product_price,
                'voucher_discount' => $this->voucher_discount_amount,
                'shipping_fee' => $this->shipping_fee,
                'original_shipping_fee' => $this->original_shipping_fee,
                'total_payment' => $this->total_payment,
                'total_weight' => $this->total_weight,
            ],

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => OrderItemResource::collection(
                $this->whenLoaded('items')
            ),

            /*
            |--------------------------------------------------------------------------
            | Cancellation
            |--------------------------------------------------------------------------
            */

            'cancellation_request' =>
                new CancellationRequestResource(
                    $this->whenLoaded('cancellationRequest')
                ),

            /*
            |--------------------------------------------------------------------------
            | Timeline
            |--------------------------------------------------------------------------
            */

            'timeline' =>
                OrderStatusHistoryResource::collection(
                    $this->whenLoaded('statusHistories')
                ),

            /*
            |--------------------------------------------------------------------------
            | Date
            |--------------------------------------------------------------------------
            */

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Status Label
    |--------------------------------------------------------------------------
    */

    protected function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Pembayaran',
            'paid' => 'Pembayaran Diterima',
            'processing' => 'Sedang Diproses',
            'picked_up' => 'Diserahkan Ekspedisi',
            'shipped' => 'Dalam Perjalanan',
            'completed' => 'Pesanan Diterima',
            'req_cancel' => 'Menunggu Persetujuan Pembatalan',
            'cancelled' => 'Pesanan Dibatalkan',
            default => ucfirst($this->status),
        };
    }
}