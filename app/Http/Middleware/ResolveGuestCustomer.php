<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Customer\CustomerSessionService;
use RuntimeException;

class ResolveGuestCustomer
{
    public function __construct(
        protected CustomerSessionService $customerSessionService,
    ) {}

    /**
     * Resolve Guest Customer
     */
    public function handle(
        Request $request,
        Closure $next,
    ): Response {

        $guestToken = $request->cookie(
            config('customer.guest_cookie_name')
        );

        if (!$guestToken) {
            throw new RuntimeException(
                'Guest session tidak ditemukan.'
            );
        }

        $customer = $this->customerSessionService
            ->findByToken($guestToken);

        if (!$customer) {
            throw new RuntimeException(
                'Guest session tidak valid.'
            );
        }

        $request->attributes->set(
            'customer',
            $customer
        );

        return $next($request);
    }
}