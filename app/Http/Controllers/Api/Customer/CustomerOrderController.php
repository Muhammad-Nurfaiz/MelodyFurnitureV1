<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\Customer\CustomerOrderService;
use App\Http\Resources\Customer\OrderTrackingResponseResource;
use App\Http\Resources\Customer\PaymentInformationResource;

class CustomerOrderController extends Controller
{
    public function __construct(
        protected CustomerOrderService $service,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Tracking Order
    |--------------------------------------------------------------------------
    */
    public function tracking(string $trackingToken): OrderTrackingResponseResource {
        $data = $this->service->tracking($trackingToken);
        return new OrderTrackingResponseResource($data);
    }

    public function paymentInformation(string $trackingToken): PaymentInformationResource {
        return new PaymentInformationResource($this->service->paymentInformation($trackingToken));
    }
}