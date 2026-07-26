<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\MidtransWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    public function __construct(
        protected MidtransWebhookService $webhookService,
    ) {}

    /**
     * Midtrans Webhook
     */
    public function __invoke(
        MidtransWebhookRequest $request
    ): JsonResponse {

        $this->webhookService
            ->handle($request->payload());

        return response()->json([
            'success' => true,
        ]);
    }
}