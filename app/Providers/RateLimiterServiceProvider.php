<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Named HTTP rate limiters for throttle:{name} middleware (see config/rate_limiting.php).
 * When rate_limiting.enabled is false, every limiter returns Limit::none().
 */
class RateLimiterServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        $off = fn (): bool => ! config('rate_limiting.enabled', true);

        RateLimiter::for('registration', function (Request $request) use ($off) {
            if ($off()) {
                return Limit::none();
            }

            return Limit::perMinutes(
                (int) config('rate_limiting.registration.decay_minutes', 60),
                (int) config('rate_limiting.registration.max_attempts', 5),
            )->by($request->ip());
        });

        RateLimiter::for('login', function (Request $request) use ($off) {
            if ($off()) {
                return Limit::none();
            }

            return Limit::perMinute((int) config('rate_limiting.login.per_minute', 10))
                ->by($request->ip());
        });

        RateLimiter::for('otp', function (Request $request) use ($off) {
            if ($off()) {
                return Limit::none();
            }

            return Limit::perMinute((int) config('rate_limiting.otp.per_minute', 6))
                ->by($request->ip());
        });

        RateLimiter::for('password_reset', function (Request $request) use ($off) {
            if ($off()) {
                return Limit::none();
            }

            return Limit::perMinutes(
                (int) config('rate_limiting.password_reset.decay_minutes', 60),
                (int) config('rate_limiting.password_reset.max_attempts', 5),
            )->by($request->ip());
        });

        RateLimiter::for('verification', function (Request $request) use ($off) {
            if ($off()) {
                return Limit::none();
            }

            $key = $request->user()?->id
                ? 'uid:'.$request->user()->id
                : $request->ip();

            return Limit::perMinute((int) config('rate_limiting.verification.per_minute', 6))
                ->by($key);
        });

        RateLimiter::for('sensitive_auth', function (Request $request) use ($off) {
            if ($off()) {
                return Limit::none();
            }

            return Limit::perMinute((int) config('rate_limiting.sensitive_auth.per_minute', 10))
                ->by((string) $request->user()?->id);
        });

        RateLimiter::for('payments_gateway', function (Request $request) use ($off) {
            if ($off()) {
                return Limit::none();
            }

            return Limit::perMinute((int) config('rate_limiting.payments_gateway.per_minute', 20))
                ->by($request->ip());
        });

        RateLimiter::for('donor_payments', function (Request $request) use ($off) {
            if ($off()) {
                return Limit::none();
            }

            return Limit::perMinute((int) config('rate_limiting.donor_payments.per_minute', 15))
                ->by((string) $request->user()?->id);
        });

        RateLimiter::for('notifications', function (Request $request) use ($off) {
            if ($off()) {
                return Limit::none();
            }

            return Limit::perMinute((int) config('rate_limiting.notifications.per_minute', 120))
                ->by((string) $request->user()?->id);
        });

        RateLimiter::for('profile_photo', function (Request $request) use ($off) {
            if ($off()) {
                return Limit::none();
            }

            return Limit::perMinute((int) config('rate_limiting.profile_photo.per_minute', 20))
                ->by((string) $request->user()?->id);
        });

        RateLimiter::for('application_resubmit', function (Request $request) use ($off) {
            if ($off()) {
                return Limit::none();
            }

            return Limit::perMinutes(
                (int) config('rate_limiting.application_resubmit.decay_minutes', 60),
                (int) config('rate_limiting.application_resubmit.max_attempts', 10),
            )->by((string) $request->user()?->id);
        });
    }
}
