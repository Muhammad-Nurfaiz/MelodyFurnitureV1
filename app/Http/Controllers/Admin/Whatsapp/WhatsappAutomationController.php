<?php

namespace App\Http\Controllers\Admin\Whatsapp;

use App\Http\Controllers\Controller;
use App\Models\WhatsappQueue;
use App\Services\Whatsapp\WhatsappNotificationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class WhatsappAutomationController extends Controller
{
    public function __construct(
        protected WhatsappNotificationService $whatsappNotificationService,
    ) {}

    /**
     * WhatsApp Automation Dashboard.
     */
    public function index(Request $request): View
    {
        $status = $request->input('status');

        $query = WhatsappQueue::query()
            ->latest();

        if ($status && in_array($status, [
            'pending',
            'processing',
            'success',
            'failed',
        ], true)) {
            $query->where('status', $status);
        }

        $queues = $query->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => WhatsappQueue::query()->count(),

            'pending' => WhatsappQueue::query()
                ->where('status', 'pending')
                ->count(),

            'processing' => WhatsappQueue::query()
                ->where('status', 'processing')
                ->count(),

            'success' => WhatsappQueue::query()
                ->where('status', 'success')
                ->count(),

            'failed' => WhatsappQueue::query()
                ->where('status', 'failed')
                ->count(),
        ];

        return view(
            'admin.modules.whatsapp.index',
            compact(
                'queues',
                'stats'
            )
        );
    }

    /**
     * Mengambil data WhatsApp queue untuk polling dashboard.
     */
    public function queues(Request $request): JsonResponse
    {
        $status = $request->input('status');

        $query = WhatsappQueue::query()
            ->latest();

        if ($status && in_array($status, [
            'pending',
            'processing',
            'success',
            'failed',
        ], true)) {
            $query->where('status', $status);
        }

        $queues = $query
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => WhatsappQueue::query()->count(),

            'pending' => WhatsappQueue::query()
                ->where('status', 'pending')
                ->count(),

            'processing' => WhatsappQueue::query()
                ->where('status', 'processing')
                ->count(),

            'success' => WhatsappQueue::query()
                ->where('status', 'success')
                ->count(),

            'failed' => WhatsappQueue::query()
                ->where('status', 'failed')
                ->count(),
        ];

        return response()->json([
            'success' => true,

            'data' => [
                'items' => $queues->items(),

                'pagination' => [
                    'current_page' => $queues->currentPage(),
                    'last_page' => $queues->lastPage(),
                    'per_page' => $queues->perPage(),
                    'total' => $queues->total(),
                ],

                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Retry failed WhatsApp message.
     */
    public function retry(string $id): JsonResponse
    {
        try {
            $queue = WhatsappQueue::findOrFail($id);

            $this->whatsappNotificationService->retry($queue);

            return response()->json([
                'success' => true,
                'message' => 'Pesan WhatsApp berhasil dimasukkan kembali ke queue.',
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Queue WhatsApp tidak ditemukan.',
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}