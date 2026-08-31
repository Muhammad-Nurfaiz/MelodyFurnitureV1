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

    public function show(
        string $trackingToken
    ): JsonResponse {

        $result = $this->paymentResultService
            ->result($trackingToken);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}