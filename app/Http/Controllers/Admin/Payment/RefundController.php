<?php

namespace App\Http\Controllers\Admin\Payment;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Services\Payment\RefundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RefundController extends Controller
{
    public function __construct(
        protected RefundService $refundService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Start Refund
    |--------------------------------------------------------------------------
    */

    public function start(Refund $refund): JsonResponse
    {
        try {

            $admin = auth()->user();

            $refund = $this->refundService->start(
                refund: $refund,
                admin: $admin,
            );

            return response()->json([
                'success' => true,
                'message' => 'Refund berhasil diproses.',
                'data' => $refund,
            ]);

        } catch (RuntimeException $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Complete Refund
    |--------------------------------------------------------------------------
    */

    public function complete(
        Request $request,
        Refund $refund
    ): JsonResponse {

        try {

            $admin = auth()->user();

            $refund = $this->refundService->complete(
                refund: $refund,
                admin: $admin,
                notes: $request->input('notes'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Refund berhasil diselesaikan.',
                'data' => $refund,
            ]);

        } catch (RuntimeException $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reject Refund
    |--------------------------------------------------------------------------
    */

    public function reject(
        Request $request,
        Refund $refund
    ): JsonResponse {

        $request->validate([
            'notes' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        try {

            $admin = auth()->user();

            $refund = $this->refundService->reject(
                refund: $refund,
                admin: $admin,
                notes: $request->input('notes'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Refund berhasil ditolak.',
                'data' => $refund,
            ]);

        } catch (RuntimeException $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}