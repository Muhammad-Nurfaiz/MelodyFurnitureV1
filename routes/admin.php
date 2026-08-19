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
use App\Http\Controllers\Admin\Voucher\VoucherController;
use App\Http\Controllers\Admin\Whatsapp\WhatsappConnectionController;
use App\Http\Controllers\Admin\Whatsapp\WhatsappAutomationController;
use App\Http\Controllers\Admin\Shipping\ShippingRateController;
use App\Http\Controllers\Admin\Document\InvoiceController;
use App\Http\Controllers\Admin\Document\PackingLabelController;
use App\Http\Controllers\Admin\Export\OrderExportController;
use App\Http\Controllers\Admin\Export\VoucherUsageExportController;
use App\Http\Controllers\Admin\Notification\NotificationController;

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::patch('/notifications/{notification}/read',[NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::patch('/notifications/read-all',[NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::delete('/notifications/{notification}',[NotificationController::class, 'destroy'])->name('notifications.destroy');

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
        Route::get('/orders/export',[OrderExportController::class, 'export'])->name('orders.export');
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

        /*
        |--------------------------------------------------------------------------
        | Voucher Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('vouchers')->name('vouchers.')->controller(VoucherController::class)->group(function () {
                Route::get('/', 'index')->name('index');
                Route::get('/create', 'create')->name('create');
                Route::post('/', 'store')->name('store');
                Route::get('/{voucher}/export-usage',[VoucherUsageExportController::class, 'export'])->name('export-usage');
                Route::get('/{id}', 'show')->name('show');
                Route::get('/{id}/edit', 'edit')->name('edit');
                Route::put('/{id}', 'update')->name('update');
                Route::delete('/{id}', 'destroy')->name('destroy');
                Route::patch('/{id}/toggle-active', 'toggleActive')->name('toggle-active');
            });

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Automation
        |--------------------------------------------------------------------------
        */

        Route::get('/whatsapp', [WhatsappAutomationController::class, 'index'])->name('whatsapp.index');
        Route::get('whatsapp/queues',[WhatsappAutomationController::class, 'queues'])->name('whatsapp.queues');
        Route::post('whatsapp/{id}/retry',[WhatsappAutomationController::class, 'retry'])->name('whatsapp.retry');
        Route::prefix('/whatsapp/connection')->name('whatsapp.connection.')->group(function () {
                Route::get('/status', [WhatsappConnectionController::class,'status'])->name('status');
                Route::post('/connect', [WhatsappConnectionController::class,'connect'])->name('connect');
                Route::get('/qr', [WhatsappConnectionController::class, 'qr'])->name('qr');
                Route::post('/stop', [WhatsappConnectionController::class,'stop'])->name('stop');
                Route::post('/restart', [WhatsappConnectionController::class,'restart'])->name('restart');
                Route::post('/logout', [WhatsappConnectionController::class,'logout'])->name('logout');
            });

        /*
        |--------------------------------------------------------------------------
        | Shipping Rate Management
        |--------------------------------------------------------------------------
        */

        Route::prefix('shipping-rates') ->name('shipping-rates.') ->controller(ShippingRateController::class) ->group(function () {
                Route::get('/', 'index') ->name('index');
                Route::get('/{shippingRate}/edit', 'edit') ->name('edit');
                Route::patch('/{shippingRate}', 'update') ->name('update');
            });

        Route::get('orders/{order}/invoice',[InvoiceController::class, 'show'])->name('orders.invoice');
        Route::get('orders/{order}/invoice/download',[InvoiceController::class, 'download'])->name('orders.invoice.download');
        Route::get('/orders/{order}/packing-label',[PackingLabelController::class, 'show'])->name('orders.packing-label');
        Route::get('/orders/{order}/packing-label/download',[PackingLabelController::class, 'download'])->name('orders.packing-label.download');
    });