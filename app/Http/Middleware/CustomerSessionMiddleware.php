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

        $guestToken = $request->cookie(
            $this->cookieName()
        );

        if (!$guestToken) {
            abort(
                response()->json([
                    'message' => 'Guest session tidak ditemukan.',
                ], 401)
            );
        }

        $customer = $this->customerSessionService
            ->findByToken($guestToken);

        if (!$customer) {
            abort(
                response()->json([
                    'message' => 'Guest session tidak valid.',
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
        | Refresh Cookie
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