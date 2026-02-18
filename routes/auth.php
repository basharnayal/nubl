<?php

/**
 * AUTH ROUTES
 *
 * Flow:
 * - Register: donor|recipient → POST /register | provider → GET /register/provider → POST
 * - Provider & Recipient: status=pending_approval until admin approves
 */

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ProviderRegistrationController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\OtpLoginController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// Guest only (not logged in)
Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::post('register/provider', [ProviderRegistrationController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::post('login/otp/request', [OtpLoginController::class, 'requestOtp'])
        ->middleware('throttle:6,1')
        ->name('login.otp.request');

    Route::post('login/otp/verify', [OtpLoginController::class, 'verify'])
        ->middleware('throttle:6,1')
        ->name('login.otp.verify');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

// Authenticated users only
Route::middleware('auth')->group(function () {
    Route::get('verify-phone', [PhoneVerificationController::class, 'show'])
        ->name('verification.phone');
    Route::post('verify-phone', [PhoneVerificationController::class, 'verify'])
        ->middleware('throttle:6,1')
        ->name('verification.phone.verify');
    Route::post('verify-phone/resend', [PhoneVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.phone.resend');

    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
