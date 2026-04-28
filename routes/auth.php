<?php

/**
 * AUTH ROUTES
 *
 * Flow:
 * - Register: donor|recipient → POST /register | provider → GET /register/provider → POST
 * - Provider & Recipient: status=pending_approval until admin approves
 *
 * Named rate limiters are defined in AppServiceProvider::configureRateLimiting (config/rate_limiting.php).
 */

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\ProviderRegistrationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// Guest only (not logged in)
Route::middleware('guest')->group(function () {
    Route::middleware('throttle:registration')->group(function () {
        Route::get('register', [RegisteredUserController::class, 'create'])
            ->name('register');

        Route::post('register', [RegisteredUserController::class, 'store']);

        Route::post('register/provider', [ProviderRegistrationController::class, 'store']);
    });

    Route::middleware('throttle:login')->group(function () {
        Route::get('login', [AuthenticatedSessionController::class, 'create'])
            ->name('login');

        Route::post('login', [AuthenticatedSessionController::class, 'store']);
    });

    Route::middleware('throttle:otp')->group(function () {
        Route::post('login/otp/request', [OtpLoginController::class, 'requestOtp'])
            ->name('login.otp.request');

        Route::post('login/otp/verify', [OtpLoginController::class, 'verify'])
            ->name('login.otp.verify');
    });

    Route::middleware('throttle:password_reset')->group(function () {
        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
            ->name('password.request');

        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
            ->name('password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
            ->name('password.reset');

        Route::post('reset-password', [NewPasswordController::class, 'store'])
            ->name('password.store');
    });
});

// Authenticated users only
Route::middleware('auth')->group(function () {
    Route::get('verify-phone', [PhoneVerificationController::class, 'show'])
        ->name('verification.phone');

    Route::middleware('throttle:verification')->group(function () {
        Route::post('verify-phone', [PhoneVerificationController::class, 'verify'])
            ->name('verification.phone.verify');
        Route::post('verify-phone/resend', [PhoneVerificationController::class, 'resend'])
            ->name('verification.phone.resend');

        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware('signed')
            ->name('verification.verify');

        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->name('verification.send');
    });

    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::middleware('throttle:sensitive_auth')->group(function () {
        Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

        Route::put('password', [PasswordController::class, 'update'])->name('password.update');
    });

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});