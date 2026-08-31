<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',

        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));
        },
    )

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'guest.customer' =>
                \App\Http\Middleware\ResolveGuestCustomer::class,

            'customer.session' =>
                \App\Http\Middleware\CustomerSessionMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {

        /*
        |--------------------------------------------------------------------------
        | Validation Exception
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            ValidationException $e,
            Request $request
        ) {

            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);

        });

        $exceptions->render(function (
            \RuntimeException $e,
            Request $request
        ) {

            if (! $request->is('api/*')) {
                return null;
            }

            /*
            |--------------------------------------------------------------------------
            | Preserve Explicit HTTP Responses
            |--------------------------------------------------------------------------
            */

            if ($e instanceof HttpResponseException) {
                return $e->getResponse();
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => null,
            ], 422);
        });

        /*
        |--------------------------------------------------------------------------
        | Authentication Exception
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            AuthenticationException $e,
            Request $request
        ) {

            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'errors' => null,
            ], 401);

        });

        /*
        |--------------------------------------------------------------------------
        | Authorization Exception
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            AuthorizationException $e,
            Request $request
        ) {

            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Forbidden.',
                'errors' => null,
            ], 403);

        });

        /*
        |--------------------------------------------------------------------------
        | Model Not Found
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            ModelNotFoundException $e,
            Request $request
        ) {

            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Resource not found.',
                'errors' => null,
            ], 404);

        });

        /*
        |--------------------------------------------------------------------------
        | HTTP Exception
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            HttpExceptionInterface $e,
            Request $request
        ) {

            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
                    ?: 'HTTP request failed.',
                'errors' => null,
            ], $e->getStatusCode());

        });

        /*
        |--------------------------------------------------------------------------
        | Unexpected API Exception
        |--------------------------------------------------------------------------
        */

        $exceptions->render(function (
            Throwable $e,
            Request $request
        ) {

            if (! $request->is('api/*')) {
                return null;
            }

            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.',
                'errors' => null,
            ], 500);
        });

    })

    ->withSchedule(function (Schedule $schedule): void {
        $schedule
            ->command('temporary-media:cleanup')
            ->everyMinute();
    })

    ->create();