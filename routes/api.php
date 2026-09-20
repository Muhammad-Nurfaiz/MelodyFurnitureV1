<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Customer\CustomerOrderController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\DirectCheckoutController;
use App\Http\Controllers\Api\MidtransWebhookController;
use App\Http\Controllers\Api\Customer\CustomerSessionController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderTrackingController;
use App\Http\Controllers\Api\ResumePaymentController;
use App\Http\Controllers\Api\PaymentResultController;
use App\Http\Controllers\Api\Customer\CustomerOrderCancellationController;
use App\Http\Controllers\Api\ShippingController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\Customer\CustomerInvoiceController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\HomeContentController;
use App\Http\Controllers\Api\JntCargoWebhookController;
use App\Http\Controllers\Api\VoucherController;

Route::prefix('tracking')->group(function () {
    Route::get('/{trackingToken}',[CustomerOrderController::class, 'tracking']);
    Route::get('/{trackingToken}/payment',[CustomerOrderController::class, 'paymentInformation']);
    Route::get('/{trackingToken}/invoice', [CustomerInvoiceController::class, 'download']);
    Route::post('/{trackingToken}/cancellation',[CustomerOrderCancellationController::class, 'store']);
});

Route::middleware('customer.session')->post('/checkout', [CheckoutController::class, 'store']);

Route::middleware('customer.session')->post('/checkout/direct', [DirectCheckoutController::class, 'store']);
Route::post('/customer/session', [CustomerSessionController::class, 'store']);

Route::middleware('customer.session')->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'index']);
    Route::post('/items', [CartController::class, 'store']);
    Route::patch('/items/{item}', [CartController::class, 'update']);
    Route::delete('/items/{item}', [CartController::class, 'destroy']);
    Route::delete('/', [CartController::class, 'clear']);
});

Route::get('/orders/track/{trackingToken}', [OrderTrackingController::class, 'show']);
Route::get('/payments/resume/{trackingToken}', [ResumePaymentController::class, 'show']);
Route::get('/payment/result/{trackingToken}', [PaymentResultController::class, 'show']);
Route::post('/payment/notification', MidtransWebhookController::class);
Route::middleware('customer.session')->post('/shipping/estimate', [ShippingController::class, 'estimate']);
Route::middleware('customer.session')->post('/shipping/estimate-all', [ShippingController::class, 'estimateAll']);
Route::get('/shipping/couriers', [ShippingController::class, 'couriers']);
Route::post('/vouchers/check', [VoucherController::class, 'check']);

Route::prefix('locations')->group(function () {
    Route::get('/provinces',[LocationController::class, 'provinces']);
    Route::get('/provinces/{provinceId}/regencies',[LocationController::class, 'regencies']);
});

Route::prefix('products')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/{slug}',[ProductController::class, 'show']);
    Route::get('/{slug}/recommendations',[ProductController::class, 'recommendations']);
});

Route::prefix('home')->group(function () {
    Route::get('/heroes',[HomeContentController::class, 'heroes']);
    Route::get('/promos',[HomeContentController::class, 'promos']);
    Route::get('/social-media',[HomeContentController::class, 'socialMedia']);
});

Route::get('/categories',[CatalogController::class, 'categories']);
Route::get('/series',[CatalogController::class, 'series']);
Route::post('/webhooks/jnt-cargo/status',[JntCargoWebhookController::class, 'status']);
Route::post('/webhooks/jnt-cargo/order-status',[JntCargoWebhookController::class, 'orderStatus']);