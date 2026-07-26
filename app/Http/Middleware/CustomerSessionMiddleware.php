<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class CustomerSessionMiddleware
{
    /**
     * Cookie Name
     */
    private const COOKIE_NAME = 'customer_session';

    /**
     * Handle Request
     */
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $token = $request->cookie(
            self::COOKIE_NAME
        );

        /*
        |--------------------------------------------------------------------------
        | Existing Customer
        |--------------------------------------------------------------------------
        */

        $customer = null;

        if ($token) {

            $customer = Customer::where(
                'guest_token',
                $token
            )->first();

        }

        /*
        |--------------------------------------------------------------------------
        | New Guest
        |--------------------------------------------------------------------------
        */

        if (!$customer) {

            $customer = Customer::create([

                'guest_token' => Str::uuid(),

            ]);

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
        | Continue
        |--------------------------------------------------------------------------
        */

        $response = $next($request);

        /*
        |--------------------------------------------------------------------------
        | Save Cookie
        |--------------------------------------------------------------------------
        */

        cookie()->queue(

            cookie(
                self::COOKIE_NAME,
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