<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Customer\CustomerOrderController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\MidtransWebhookController;
use App\Http\Controllers\Api\Customer\CustomerSessionController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderTrackingController;
use App\Http\Controllers\Api\ResumePaymentController;
use App\Http\Controllers\Api\PaymentResultController;
use App\Http\Controllers\Api\Customer\CustomerOrderCancellationController;

/*
|--------------------------------------------------------------------------
| Customer Tracking
|--------------------------------------------------------------------------
*/

Route::prefix('tracking')
    ->group(function () {

        Route::get('/{trackingToken}',[CustomerOrderController::class, 'tracking']);
        Route::get('/{trackingToken}/payment',[CustomerOrderController::class, 'paymentInformation']);
        Route::post('/{trackingToken}/cancellation',[CustomerOrderCancellationController::class, 'store']);
    });

Route::middleware('guest.customer')->post('/checkout',[CheckoutController::class,'store']);
Route::post('/customer/session',[CustomerSessionController::class, 'store']);
    
Route::middleware('customer.session')
    ->prefix('cart')
    ->group(function () {

        Route::get('/',[CartController::class,'index']);
        Route::post('/items',[CartController::class,'store']);
        Route::patch('/items/{item}',[CartController::class,'update']);
        Route::delete('/items/{item}',[CartController::class,'destroy']);
        Route::delete('/',[CartController::class,'clear']);
    });

Route::get('/items/{item}',[CartController::class,'destroy']);
Route::get('/orders/track/{trackingToken}',[OrderTrackingController::class, 'show']);
Route::get('/payments/resume/{trackingToken}',[ResumePaymentController::class, 'show']);
Route::get('/payment/result',[PaymentResultController::class, 'show']);
Route::post('/payment/notification', MidtransWebhookController::class);

