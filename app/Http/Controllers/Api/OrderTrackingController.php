<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderTrackingResource;
use App\Services\Order\OrderTrackingService;
use Illuminate\Http\Request;

class OrderTrackingController extends Controller
{
    public function __construct(
        protected OrderTrackingService $trackingService,
    ) {}

    /**
     * Customer Tracking Order
     */
    public function show(
        string $trackingToken
    ): OrderTrackingResource {

        $order = $this->trackingService
            ->findByTrackingToken($trackingToken);

        return new OrderTrackingResource($order);
    }
}