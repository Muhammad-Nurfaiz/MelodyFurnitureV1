<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Services\Order\CustomerOrderService;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\Customer\OrderTrackingResource;

class CustomerOrderController extends Controller
{
    public function __construct(
        protected CustomerOrderService $customerOrderService
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Tracking Order
    |--------------------------------------------------------------------------
    */

    public function tracking(
        string $trackingToken
    ): OrderTrackingResource
    {

        return new OrderTrackingResource(

            $this->customerOrderService
                ->trackingDetail(
                    $trackingToken
                )

        );

    }

    /*
    |--------------------------------------------------------------------------
    | Can Request Cancellation
    |--------------------------------------------------------------------------
    */

    public function canRequestCancellation(
        string $trackingToken
    ): JsonResponse {

        return response()->json([

            'success' => true,

            'can_cancel' =>

                $this->customerOrderService
                    ->canRequestCancellation(
                        $trackingToken
                    ),

        ]);

    }

    /*
    |--------------------------------------------------------------------------
    | Request Cancellation
    |--------------------------------------------------------------------------
    */

    public function requestCancellation(
        string $trackingToken
    ): JsonResponse {

        return response()->json([

            'message' =>

                'Method belum diimplementasikan.'

        ]);

    }
}