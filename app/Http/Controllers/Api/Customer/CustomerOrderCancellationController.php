<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Order\OrderCancellationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CustomerOrderCancellationController extends Controller
{
    public function __construct(
        protected OrderCancellationService $cancellationService,
    ) {}

    /**
     * Request cancellation by customer.
     */
    public function store(
        Request $request,
        string $trackingToken
    ): JsonResponse {

        try {

            /*
            |--------------------------------------------------------------------------
            | Validate Request
            |--------------------------------------------------------------------------
            */

            $validated = $request->validate([
                'reason' => [
                    'required',
                    'string',
                    'min:5',
                    'max:500',
                ],
            ]);

            /*
            |--------------------------------------------------------------------------
            | Find Order
            |--------------------------------------------------------------------------
            */

            $order = Order::query()
                ->where('tracking_token', $trackingToken)
                ->first();

            if (! $order) {

                return response()->json([
                    'success' => false,
                    'message' => 'Order tidak ditemukan.',
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Request Cancellation
            |--------------------------------------------------------------------------
            */

            $cancellationRequest =
                $this->cancellationService->requestByCustomer(
                    order: $order,
                    reason: $validated['reason'],
                );

            /*
            |--------------------------------------------------------------------------
            | Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' => 'Permintaan pembatalan berhasil dikirim.',
                'data' => [
                    'order' => [
                        'id' => $cancellationRequest->order->id,
                        'order_number' => $cancellationRequest->order->order_number,
                        'status' => $cancellationRequest->order->status,
                    ],

                    'cancellation_request' => [
                        'id' => $cancellationRequest->id,
                        'reason' => $cancellationRequest->reason,
                        'previous_status' => $cancellationRequest->previous_status,
                        'status' => $cancellationRequest->status,
                        'created_at' => $cancellationRequest->created_at,
                    ],
                ],
            ], 201);

        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}