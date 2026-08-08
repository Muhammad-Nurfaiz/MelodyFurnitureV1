<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Category\CategoryController;
use App\Http\Controllers\Admin\Series\SeriesController;
use App\Http\Controllers\Admin\Product\ProductController;
use App\Http\Controllers\Admin\Product\ProductMediaController;
use App\Http\Controllers\Admin\Media\TemporaryMediaController;
use App\Http\Controllers\Admin\Order\OrderController;
use App\Http\Controllers\Admin\Shipment\ShipmentController;
use App\Http\Controllers\Admin\Order\OrderCancellationController;
use App\Http\Controllers\Admin\Payment\RefundController;
use App\Http\Controllers\Admin\Profile\ProfileController;
use App\Http\Controllers\Admin\Settings\SettingsController;
use App\Http\Controllers\Admin\Customer\CustomerController;

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::patch('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

        Route::resource('/categories', CategoryController::class)->except(['create','edit']);
        Route::resource('/series', SeriesController::class)->except(['create','edit']);
        Route::resource('/products', ProductController::class)->except(['show']);

        Route::post('media/temporary', [TemporaryMediaController::class, 'store'])->name('media.temporary.store');
        Route::delete('media/temporary/{id}', [TemporaryMediaController::class, 'destroy'])->name('media.temporary.destroy');
        Route::delete('media/temporary/cleanup', [TemporaryMediaController::class, 'cleanup'])->name('media.temporary.cleanup');
        Route::delete('products/media/{media}', [ProductMediaController::class, 'destroy'])->name('products.media.destroy');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class,'show'])->name('orders.show');
        Route::patch('/orders/{order}/processing', [OrderController::class, 'processing'])->name('orders.processing');

        Route::patch('/order-cancellation/{cancellationRequest}/approve',[OrderCancellationController::class, 'approve'])->name('orders.cancellation.approve');
        Route::patch('/order-cancellation/{cancellationRequest}/reject',[OrderCancellationController::class, 'reject'])->name('orders.cancellation.reject');
        Route::patch('/orders/{order}/cancel',[OrderCancellationController::class, 'cancel'])->name('orders.cancel');
            
        Route::patch('/refunds/{refund}/start',[RefundController::class, 'start'])->name('refunds.start');
        Route::patch('/refunds/{refund}/complete',[RefundController::class, 'complete'])->name('refunds.complete');
        Route::patch('/refunds/{refund}/reject',[RefundController::class, 'reject'])->name('refunds.reject');

        Route::post('/orders/{order}/shipment', [ShipmentController::class, 'store'])->name('shipments.store');
        Route::patch('/shipments/{shipment}/pickup', [ShipmentController::class, 'pickup'])->name('shipments.pickup');
        Route::patch('/shipments/{shipment}/transit', [ShipmentController::class, 'transit'])->name('shipments.transit');
        Route::patch('/shipments/{shipment}/delivered', [ShipmentController::class, 'delivered'])->name('shipments.delivered');
        Route::patch('/shipments/{shipment}/cancel', [ShipmentController::class, 'cancel'])->name('shipments.cancel');
        
        /*
        |--------------------------------------------------------------------------
        | Settings
        |--------------------------------------------------------------------------
        */
        Route::get('/settings',[SettingsController::class, 'index'])->name('settings.index');
        Route::patch('/settings/store',[SettingsController::class, 'updateStore'])->name('settings.store.update');
        Route::patch('settings/branding',[SettingsController::class, 'updateBranding'])->name('settings.branding.update');
        Route::post('/settings/hero',[SettingsController::class, 'storeHero'])->name('settings.hero.store');
        Route::patch('/settings/hero/{heroSlide}',[SettingsController::class, 'updateHero'])->name('settings.hero.update');
        Route::delete('/settings/hero/{heroSlide}',[SettingsController::class, 'destroyHero'])->name('settings.hero.destroy');
        Route::post('/settings/promo',[SettingsController::class, 'storePromo'])->name('settings.promo.store');
        Route::patch('/settings/promo/sort',[SettingsController::class, 'sortPromo'])->name('settings.promo.sort');
        Route::patch('/settings/promo/{promoBanner}',[SettingsController::class, 'updatePromo'])->name('settings.promo.update');
        Route::delete('/settings/promo/{promoBanner}',[SettingsController::class, 'destroyPromo'])->name('settings.promo.destroy');

        /*
        |--------------------------------------------------------------------------
        | Customer Management
        |--------------------------------------------------------------------------
        */

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    });