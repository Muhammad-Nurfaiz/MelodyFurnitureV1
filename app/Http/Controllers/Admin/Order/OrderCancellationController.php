<?php

namespace App\Http\Controllers\Admin\Order;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Order;
use App\Models\OrderCancelRequest;
use App\Services\Order\OrderCancellationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class OrderCancellationController extends Controller
{
    public function __construct(
        protected OrderCancellationService $cancellationService,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Approve Cancellation
    |--------------------------------------------------------------------------
    */

    public function approve(
        Request $request,
        OrderCancelRequest $cancellationRequest
    ): RedirectResponse {

        try {

            $admin = $request->user();

            /*
            |--------------------------------------------------------------------------
            | Pastikan authenticated user adalah Admin
            |--------------------------------------------------------------------------
            */

            if (! $admin instanceof Admin) {
                abort(403);
            }

            $this->cancellationService->approve(
                request: $cancellationRequest,
                admin: $admin,
                notes: $request->input('admin_notes')
            );

            return redirect()
                ->route(
                    'admin.orders.show',
                    $cancellationRequest->order
                )
                ->with(
                    'success',
                    'Permintaan pembatalan berhasil disetujui.'
                );

        } catch (Throwable $e) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Reject Cancellation
    |--------------------------------------------------------------------------
    */

    public function reject(
        Request $request,
        OrderCancelRequest $cancellationRequest
    ): RedirectResponse {

        try {

            $admin = $request->user();

            if (! $admin instanceof Admin) {
                abort(403);
            }

            $this->cancellationService->reject(
                request: $cancellationRequest,
                admin: $admin,
                notes: $request->input('admin_notes')
            );

            return redirect()
                ->route(
                    'admin.orders.show',
                    $cancellationRequest->order
                )
                ->with(
                    'success',
                    'Permintaan pembatalan ditolak.'
                );

        } catch (Throwable $e) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    public function cancel(Request $request, Order $order): RedirectResponse | JsonResponse {
        try {
            $admin = $request->user();
            if (! $admin instanceof Admin) {
                abort(403);
            }

            $request->validate([
                'reason' => [
                    'required',
                    'string',
                    'max:500',
                ],
            ]);

            $this->cancellationService->cancelByAdmin(
                order: $order,
                admin: $admin,
                reason: $request->input('reason')
            );

            if ($request->expectsJson()) {

                return response()->json([
                    'success' => true,
                    'message' => 'Order berhasil dibatalkan.',
                    'redirect' => route(
                        'admin.orders.show',
                        $order
                    ),
                ]);
            }

            return redirect()
                ->route(
                    'admin.orders.show',
                    $order
                )
                ->with(
                    'success',
                    'Order berhasil dibatalkan.'
                );

        } catch (Throwable $e) {

            if ($request->expectsJson()) {

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()
                ->back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }
}