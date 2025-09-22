<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Http\Controllers\CsrfCookieController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/health', function () {
    return "Health check";
});


// Named login route for middleware redirects
Route::get('/login', function () {
    return response()->json(['message' => 'Please log in via /api/login'], 401);
})->name('login');



// Sanctum CSRF cookie route
Route::get('/sanctum/csrf-cookie', [CsrfCookieController::class, 'show'])
    ->middleware('web');

Route::get('/test-db', function () {
    try {
        DB::connection()->getPdo();
        return 'База данных подключена!!!';
    } catch (\Exception $e) {
        return 'Unable to connect to the database: ' . $e->getMessage();
    }
});
