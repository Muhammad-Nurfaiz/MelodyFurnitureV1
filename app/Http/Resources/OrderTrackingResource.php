<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderTrackingResource extends JsonResource
{
    /**
     * Transform resource into array.
     */
    public function toArray(Request $request): array
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Identity
            |--------------------------------------------------------------------------
            */

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
            | Customer
            |--------------------------------------------------------------------------
            */

            'customer' => [
                'name'  => $this->customer?->name,
                'email' => $this->customer?->email,
                'phone' => $this->customer?->phone,
            ],

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'payment' => [
                'payment_type' => $this->payment?->payment_type,
                'bank' => $this->payment?->bank,
                'va_number' => $this->payment?->va_number,
                'transaction_status' => $this->payment?->transaction_status,
                'paid_at' => $this->payment?->paid_at,
                'expired_at' => $this->payment?->expired_at,
            ],

            /*
            |--------------------------------------------------------------------------
            | Shipping
            |--------------------------------------------------------------------------
            */

            'shipping' => [
                'courier' => $this->courier,
                'service' => $this->shipping_method,
                'tracking_number' => $this->tracking_number,
                'address' => $this->shipping_address,
            ],

            /*
            |--------------------------------------------------------------------------
            | Summary
            |--------------------------------------------------------------------------
            */

            'summary' => [
                'subtotal' => $this->total_product_price,
                'shipping_fee' => $this->shipping_fee,
                'voucher_discount' => $this->voucher_discount_amount,
                'total_payment' => $this->total_payment,
            ],

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            */

            'items' => $this->items->map(function ($item) {

                return [
                    'id' => $item->product_id,
                    'name' => $item->product_name,
                    'slug' => $item->product_slug,
                    'image' => $item->product_image,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'subtotal' => $item->subtotal,
                ];
            }),

            /*
            |--------------------------------------------------------------------------
            | Timeline
            |--------------------------------------------------------------------------
            */

            'timeline' => $this->statusHistories->map(function ($history) {

                return [
                    'status' => $history->status,
                    'description' => $history->description,
                    'actor' => $history->actor,
                    'created_at' => $history->created_at,
                ];
            }),

            /*
            |--------------------------------------------------------------------------
            | Customer Actions
            |--------------------------------------------------------------------------
            */

            'actions' => [
                'can_pay' => $this->canContinuePayment(),
                'can_request_cancel' => $this->canRequestCancel(),
                'can_track_shipping' => $this->canTrackShipment(),
                'can_download_invoice' => $this->canDownloadInvoice(),
            ],
        ];
    }
}