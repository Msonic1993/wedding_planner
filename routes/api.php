<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->controller(AuthController::class)->group(function (): void {
        Route::post('register', 'register')->middleware('throttle:6,1');
        Route::post('login', 'login')->middleware('throttle:6,1');
        Route::post('forgot-password', 'forgotPassword')->middleware('throttle:6,1');
        Route::post('reset-password', 'resetPassword')->middleware('throttle:6,1');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', 'logout');
            Route::post('email/verification-notification', 'sendVerificationNotification')->middleware('throttle:6,1');
            Route::delete('account', 'destroy');
        });

        Route::get('email/verify/{id}/{hash}', 'verifyEmail')
            ->middleware(['auth:sanctum', 'signed', 'throttle:6,1'])
            ->name('verification.verify');
    });
});
