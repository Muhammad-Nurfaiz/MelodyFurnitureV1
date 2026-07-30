<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\ResumePaymentService;
use Illuminate\Http\JsonResponse;

class ResumePaymentController extends Controller
{
    public function __construct(
        protected ResumePaymentService $resumePaymentService,
    ) {}

    /**
     * Resume pending payment.
     */
    public function show(string $trackingToken): JsonResponse
    {
        $result = $this->resumePaymentService
            ->resume($trackingToken);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}   