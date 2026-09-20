<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Customer\CustomerSessionService;

class CustomerSessionMiddleware
{
    public function __construct(
        protected CustomerSessionService $customerSessionService,
    ) {}

    private function cookieName(): string
    {
        return config('customer.guest_cookie_name');
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {

        // 🚀 BACA DARI HEADER, BODY, ATAU COOKIE
        $guestToken = $request->header('X-Guest-Session-Id')
            ?? $request->header('X-Guest-Token')
            ?? $request->input('guest_session_id')
            ?? $request->cookie($this->cookieName());

        if (!$guestToken) {
            abort(
                response()->json([
                    'success' => false,
                    'message' => 'Guest session tidak ditemukan.',
                    'errors' => null,
                ], 401)
            );
        }

        $customer = $this->customerSessionService
            ->findByToken($guestToken);

        if (!$customer) {
            abort(
                response()->json([
                    'success' => false,
                    'message' => 'Guest session tidak valid.',
                    'errors' => null,
                ], 401)
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Share Customer
        |--------------------------------------------------------------------------
        */

        $request->attributes->set(
            'customer',
            $customer
        );

        /*
        |--------------------------------------------------------------------------
        | Continue Request
        |--------------------------------------------------------------------------
        */

        $response = $next($request);

        /*
        |--------------------------------------------------------------------------
        | Refresh Cookie (Opsional)
        |--------------------------------------------------------------------------
        */

        cookie()->queue(
            cookie(
                $this->cookieName(),
                $customer->guest_token,
                60 * 24 * 365,
                '/',
                null,
                app()->environment('production'),
                true,
                false,
                'lax'
            )
        );

        return $response;
    }
}