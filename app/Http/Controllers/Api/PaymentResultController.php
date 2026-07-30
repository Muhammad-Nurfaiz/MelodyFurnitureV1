<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentResultService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentResultController extends Controller
{
    public function __construct(
        protected PaymentResultService $paymentResultService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Payment Result
    |--------------------------------------------------------------------------
    */

    public function show(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => [
                'required',
                'string',
            ],
        ]);

        $result = $this->paymentResultService->result(
            $request->string('order_id')->toString()
        );

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}