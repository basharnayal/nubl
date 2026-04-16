<?php

/**
 * Central HTTP rate limiting configuration (named limiters via RateLimiter::for).
 * Limiter registrations live in App\Providers\RateLimiterServiceProvider.
 * Set RATE_LIMITING_ENABLED=false to disable all route throttling at once (e.g. local debugging).
 */
return [

    'enabled' => env('RATE_LIMITING_ENABLED', true),

    'registration' => [
        'decay_minutes' => (int) env('RATE_LIMIT_REGISTRATION_DECAY_MINUTES', 60),
        'max_attempts' => (int) env('RATE_LIMIT_REGISTRATION_MAX', 5),
    ],

    'login' => [
        'per_minute' => (int) env('RATE_LIMIT_LOGIN_PER_MINUTE', 10),
    ],

    'otp' => [
        'per_minute' => (int) env('RATE_LIMIT_OTP_PER_MINUTE', 6),
    ],

    'password_reset' => [
        'decay_minutes' => (int) env('RATE_LIMIT_PASSWORD_RESET_DECAY_MINUTES', 60),
        'max_attempts' => (int) env('RATE_LIMIT_PASSWORD_RESET_MAX', 5),
    ],

    'verification' => [
        'per_minute' => (int) env('RATE_LIMIT_VERIFICATION_PER_MINUTE', 6),
    ],

    'sensitive_auth' => [
        'per_minute' => (int) env('RATE_LIMIT_SENSITIVE_AUTH_PER_MINUTE', 10),
    ],

    'payments_gateway' => [
        'per_minute' => (int) env('RATE_LIMIT_PAYMENTS_GATEWAY_PER_MINUTE', 20),
    ],

    'donor_payments' => [
        'per_minute' => (int) env('RATE_LIMIT_DONOR_PAYMENTS_PER_MINUTE', 15),
    ],

    'notifications' => [
        'per_minute' => (int) env('RATE_LIMIT_NOTIFICATIONS_PER_MINUTE', 120),
    ],

    'profile_photo' => [
        'per_minute' => (int) env('RATE_LIMIT_PROFILE_PHOTO_PER_MINUTE', 20),
    ],

    'application_resubmit' => [
        'decay_minutes' => (int) env('RATE_LIMIT_APPLICATION_RESUBMIT_DECAY_MINUTES', 60),
        'max_attempts' => (int) env('RATE_LIMIT_APPLICATION_RESUBMIT_MAX', 10),
    ],
];
