<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Dashboard\DashboardController;
use App\Http\Controllers\Admin\Category\CategoryController;
use App\Http\Controllers\Admin\Series\SeriesController;
use App\Http\Controllers\Admin\Product\ProductController;
use App\Http\Controllers\Admin\Product\ProductMediaController;
use App\Http\Controllers\Admin\Media\TemporaryMediaController;
use App\Http\Controllers\Admin\Order\OrderController;

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::resource('/categories', CategoryController::class)
            ->except([
                'create',
                'edit',
            ]);

        Route::resource('/series', SeriesController::class)
            ->except([
                'create',
                'edit',
            ]);
        
        Route::resource('/products', ProductController::class)
            ->except(['show']);

        Route::delete(
            'products/media/{media}',
            [ProductMediaController::class, 'destroy']
        )->name('products.media.destroy');

        Route::get('/orders', [OrderController::class, 'index'])
            ->name('orders.index');

    });

Route::prefix('admin')
    ->middleware(['auth'])
    ->name('admin.')
    ->group(function () {

        Route::post(
            'media/temporary',
            [TemporaryMediaController::class, 'store']
        )->name('media.temporary.store');

        Route::delete(
            'media/temporary/{id}',
            [TemporaryMediaController::class, 'destroy']
        )->name('media.temporary.destroy');
        
        Route::delete(
            'media/temporary/cleanup',
            [TemporaryMediaController::class, 'cleanup']
        )->name('media.temporary.cleanup');
    });