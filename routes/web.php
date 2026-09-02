<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/debug/csrf', function () {
    return response()->json([
        'csrf_token' => csrf_token(),
        'session_id' => session()->getId(),
    ]);
})->middleware('web');

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';