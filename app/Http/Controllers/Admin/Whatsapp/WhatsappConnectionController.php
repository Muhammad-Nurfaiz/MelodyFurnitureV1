<?php

namespace App\Http\Controllers\Admin\Whatsapp;

use App\Http\Controllers\Controller;
use App\Services\Whatsapp\WhatsappConnectionService;
use Illuminate\Http\JsonResponse;
use Throwable;

class WhatsappConnectionController extends Controller
{
    public function __construct(
        protected WhatsappConnectionService $whatsappService,
    ) {}

    /**
     * Dashboard connection status.
     */
    public function status(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $this->whatsappService->status(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Start WhatsApp session.
     */
    public function connect(): JsonResponse
    {
        try {
            $data = $this->whatsappService->start();

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp session berhasil dimulai.',
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Get QR code.
     */
    public function qr(): JsonResponse
    {
        try {
            $data = $this->whatsappService->qr();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Stop WhatsApp session.
     */
    public function stop(): JsonResponse
    {
        try {
            $data = $this->whatsappService->stop();

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp session dihentikan.',
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Logout WhatsApp.
     */
    public function logout(): JsonResponse
    {
        try {
            $data = $this->whatsappService->logout();

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp berhasil logout.',
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 503);
        }
    }

    /**
     * Restart WhatsApp session.
     */
    public function restart(): JsonResponse
    {
        try {
            $data = $this->whatsappService->restart();

            return response()->json([
                'success' => true,
                'message' => 'WhatsApp session berhasil direstart.',
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 503);
        }
    }
}