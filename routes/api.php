<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Customer\CustomerOrderController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\MidtransWebhookController;
use App\Http\Controllers\Api\Customer\CustomerSessionController;
use App\Http\Controllers\Api\CartController;

/*
|--------------------------------------------------------------------------
| Customer Tracking
|--------------------------------------------------------------------------
*/

Route::prefix('tracking')
    ->group(function () {

        Route::get(
            '/{trackingToken}',
            [CustomerOrderController::class, 'tracking']
        );

        Route::get(
            '/{trackingToken}/payment',
            [CustomerOrderController::class, 'paymentInformation']
        );
    });

Route::post('/payment/notification', MidtransWebhookController::class);
Route::middleware('guest.customer')
    ->post(
        '/checkout',
        [CheckoutController::class,'store']
    );

Route::post(
    '/customer/session',
    [CustomerSessionController::class, 'store']
);

Route::middleware('customer.session')
    ->prefix('cart')
    ->group(function () {

        Route::get(
            '/',
            [CartController::class,'index']
        );

        Route::post(
            '/items',
            [CartController::class,'store']
        );

        Route::patch(
            '/items/{item}',
            [CartController::class,'update']
        );

        Route::delete(
            '/items/{item}',
            [CartController::class,'destroy']
        );

        Route::delete(
            '/',
            [CartController::class,'clear']
        );

    });