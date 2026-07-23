<?php

namespace App\Http\Controllers\Payment;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Payment\MidtransWebhookService;

class NotificationController extends Controller
{
    public function __construct(
        protected MidtransWebhookService $webhookService
    ) {}

    /**
     * Webhook Midtrans
     */
    public function __invoke(
        Request $request
    ): JsonResponse {

        try {

            $this->webhookService->handle(

                $request->all()

            );

            return response()->json([

                'success' => true,

            ]);

        } catch (Throwable $e) {

            report($e);

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ],500);

        }

    }
}