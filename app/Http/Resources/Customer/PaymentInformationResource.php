<?php

namespace App\Http\Resources\Customer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentInformationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        $order = $this['order'];

        $payment = $this['payment'];

        return [

            /*
            |--------------------------------------------------------------------------
            | Order
            |--------------------------------------------------------------------------
            */

            'order_number' => $order->order_number,

            'tracking_token' => $order->tracking_token,

            'status' => $order->status,

            'payment_status' => $order->payment_status,

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            'payment_method' => $payment?->payment_method,

            'snap_token' => $payment?->snap_token,

            'redirect_url' => $payment?->redirect_url,

            'transaction_id' => $payment?->transaction_id,

            /*
            |--------------------------------------------------------------------------
            | Amount
            |--------------------------------------------------------------------------
            */

            'total_payment' => $order->total_payment,

            /*
            |--------------------------------------------------------------------------
            | Expired
            |--------------------------------------------------------------------------
            */

            'expired_at' => $order->payment_expired_at?->toIso8601String(),

            'remaining_seconds' => $this['remaining_seconds'],

            'is_expired' => $this['expired'],

            'can_pay' => $this['can_pay'],

            /*
            |--------------------------------------------------------------------------
            | Paid
            |--------------------------------------------------------------------------
            */

            'paid_at' => $order->paid_at?->toIso8601String(),

        ];

    }
}