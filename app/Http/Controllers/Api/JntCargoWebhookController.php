<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Shipping\Webhooks\JntCargoWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class JntCargoWebhookController extends Controller
{
    public function __construct(
        protected JntCargoWebhookService $webhookService,
    ) {}

    /**
     * J&T Cargo Logistics Trajectory Return Webhook.
     */
    public function __invoke(
        Request $request
    ): JsonResponse {
        try {
            $result = $this->webhookService->handle(
                $request->all()
            );

            return response()->json([
                'success' => true,
                'message' => 'Webhook J&T Cargo berhasil diproses.',
                'data' => $result,
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Webhook J&T Cargo gagal diproses.',
            ], 500);
        }
    }

    public function orderStatus(Request $request): JsonResponse
    {
        try {
            $result = $this->webhookService->handleOrderStatus($request->all());

            return response()->json([
                'code' => '1',
                'msg' => 'success',
                'data' => 'SUCCESS',
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'code' => '0',
                'msg' => 'fail',
                'data' => 'FAIL',
            ], 500);
        }
    }
}