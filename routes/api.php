<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Authentication routes (public)
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1')
    ->name('login');
Route::post('/register', [AuthController::class, 'register'])
    ->middleware('throttle:3,1')
    ->name('register');

// Password reset routes (public)
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:3,1'); // 3 attempts per minute
Route::post('/reset-password', [AuthController::class, 'resetPassword'])
    ->middleware('throttle:5,1');

// Email verification route (no auth required)
Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('custom.email.verify');

// Protected routes (require JWT authentication)
Route::middleware(['auth.cookie', 'auth:api', 'active.user'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/admin/users', [AuthController::class, 'createManagedUser'])
        ->middleware('throttle:10,1');

    // Email verification routes
    Route::prefix('email')->group(function () {
        Route::post('/verification-notification', [AuthController::class, 'sendEmailVerification'])
            ->middleware('throttle:6,1'); // 6 attempts per minute
    });

    // Commercial proposal (admin only, checked in controller)
    Route::post('/send-commercial-proposal', [AuthController::class, 'sendCommercialProposal'])
        ->middleware('throttle:10,1'); // 10 attempts per minute
});
