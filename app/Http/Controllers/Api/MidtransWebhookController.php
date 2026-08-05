<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\MidtransWebhookService;
use App\Http\Requests\Api\MidtransWebhookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    public function __construct(
        protected MidtransWebhookService $webhookService,
    ) {}

    public function __invoke(MidtransWebhookRequest $request): JsonResponse {

        Log::info('Midtrans notification received', [
            'payload' => $request->all(),
        ]);
    
        $this->webhookService
            ->handle($request->payload());

        return response()->json([
            'success' => true,
        ]);
    }
}