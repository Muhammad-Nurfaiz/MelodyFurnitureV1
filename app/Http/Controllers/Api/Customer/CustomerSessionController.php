<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerSessionRequest;
use App\Http\Resources\CustomerResource;
use App\Services\Customer\CustomerSessionService;
use Illuminate\Http\JsonResponse;

class CustomerSessionController extends Controller
{
    public function __construct(
        protected CustomerSessionService $customerSessionService,
    ) {}

    /**
     * Create Guest Session
     */
    public function store(
        CustomerSessionRequest $request
    ): JsonResponse {

        // 1. Ambil token lama jika ada dari Header, Body, atau Cookie
        $existingToken = $request->header('X-Guest-Session-Id')
            ?? $request->header('X-Guest-Token')
            ?? $request->input('guest_token')
            ?? $request->cookie(config('customer.guest_cookie_name'));

        // 2. Resolve session via service
        $customer = $this->customerSessionService
            ->resolve(
                $existingToken,
                $request->validated()
            );

        // 3. Susun response JSON yang menyertakan guest_token secara eksplisit
        return response()
            ->json([
                'success' => true,
                'message' => 'Guest session berhasil dibuat.',
                'data' => array_merge(
                    (new CustomerResource($customer))->resolve(),
                    [
                        'guest_token' => $customer->guest_token, // 👈 PENTING: Untuk dibaca LocalStorage Next.js
                    ]
                ),
            ], 201)
            ->cookie(
                config('customer.guest_cookie_name'),
                $customer->guest_token,
                config('customer.guest_cookie_days') * 24 * 60,
                '/',
                null,
                app()->environment('production'),
                true,
                false,
                'Lax'
            );
    }
}