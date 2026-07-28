<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Customer\CustomerSessionService;

class ResolveGuestCustomer
{
    public function __construct(
        protected CustomerSessionService $customerSessionService,
    ) {}

    public function handle(
        Request $request,
        Closure $next,
    ): Response {

        $guestToken = $request->cookie(
            config('customer.guest_cookie_name')
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

        $request->attributes->set(
            'customer',
            $customer
        );

        return $next($request);
    }
}