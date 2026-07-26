<?php

namespace App\Services\Customer;

use App\Models\Order;

class CustomerOrderService
{
    public function __construct(
        protected CustomerTrackingService $trackingService,
        protected CustomerPaymentService $paymentService,
        protected CustomerCancellationService $cancellationService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Tracking Order
    |--------------------------------------------------------------------------
    */

    public function tracking(
        string $trackingToken
    ): array {

        return $this->trackingService
            ->detail(
                $trackingToken
            );

    }

    /*
    |--------------------------------------------------------------------------
    | Check Tracking Token
    |--------------------------------------------------------------------------
    */

    public function exists(
        string $trackingToken
    ): bool {

        return $this->trackingService
            ->exists(
                $trackingToken
            );

    }

    /*
    |--------------------------------------------------------------------------
    | Payment Information
    |--------------------------------------------------------------------------
    */

    public function paymentInformation(
        string $trackingToken
    ): array {

        return $this->paymentService
            ->information(
                $trackingToken
            );

    }

    /*
    |--------------------------------------------------------------------------
    | Cancellation Request
    |--------------------------------------------------------------------------
    */

    public function requestCancellation(
        string $trackingToken,
        string $reason,
        ?string $note = null,
    ): Order {

        return $this->cancellationService
            ->request(

                trackingToken: $trackingToken,

                reason: $reason,

                note: $note,

            );

    }
}