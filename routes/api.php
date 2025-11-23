<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Authentication routes (public)
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');

// Password reset routes (public)
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:3,1'); // 3 attempts per minute
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Email verification route (no auth required)
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('custom.email.verify');

// Protected routes (require JWT authentication)
Route::middleware(['auth.cookie', 'auth:api'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user', [AuthController::class, 'user']);

    // Email verification routes
    Route::prefix('email')->group(function () {
        Route::post('/verification-notification', [AuthController::class, 'sendEmailVerification'])
            ->middleware('throttle:6,1'); // 6 attempts per minute
    });
});
