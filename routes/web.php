<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MetaCatalogFeedController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/debug/csrf', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId(),
    ]);
})->middleware('web');

Route::get(
    '/feed/meta-catalog.csv',
    MetaCatalogFeedController::class
)->name('meta.catalog.feed');

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';