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

        $customer = $this->customerSessionService
            ->resolve(
                $request->cookie('guest_token'),
                $request->validated()
            );

        return response()

            ->json([

                'message' => 'Guest session berhasil dibuat.',

                'data' => new CustomerResource($customer),

            ],201)

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