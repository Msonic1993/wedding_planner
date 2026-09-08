<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InquiryChatController;
use App\Http\Controllers\Api\V1\MarketplaceController;
use App\Http\Controllers\Api\V1\VendorProfileController;
use App\Http\Controllers\Api\V1\VendorReviewController;
use App\Http\Controllers\Api\V1\WeddingProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('marketplace/vendors', [MarketplaceController::class, 'index']);
    Route::middleware(['auth:sanctum', 'role:bride|groom'])->post('vendors/{vendor}/inquiries', [InquiryChatController::class, 'store']);
    Route::middleware(['auth:sanctum', 'role:bride|groom|vendor'])->prefix('chat')->controller(InquiryChatController::class)->group(function (): void {
        Route::get('inquiries', 'index');
        Route::get('inquiries/{inquiry}/messages', 'messages');
        Route::post('inquiries/{inquiry}/messages', 'send');
    });
    Route::middleware(['auth:sanctum', 'role:bride|groom'])->group(function (): void {
        Route::post('vendors/{vendor}/review', [VendorReviewController::class, 'store']);
        Route::patch('vendors/{vendor}/review', [VendorReviewController::class, 'update']);
        Route::delete('vendors/{vendor}/review', [VendorReviewController::class, 'destroy']);
    });
    Route::get('vendor-categories', [VendorProfileController::class, 'categories']);
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

    Route::middleware(['auth:sanctum', 'role:bride|groom'])
        ->prefix('profile')
        ->controller(WeddingProfileController::class)
        ->group(function (): void {
            Route::get('/', 'show');
            Route::post('/', 'store');
            Route::patch('/', 'update');
        });
    Route::middleware(['auth:sanctum', 'role:vendor'])->prefix('vendor/profile')->controller(VendorProfileController::class)->group(function (): void {
        Route::get('/', 'show');
        Route::post('/', 'store');
        Route::patch('/', 'update');
    });
});
