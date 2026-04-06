<?php

namespace App\Providers;

use App\Contracts\AllocationEngineServiceInterface;
use App\Contracts\NotificationServiceInterface;
use App\Http\Services\AllocationEngineService;
use App\Http\Services\NotificationService;
use App\Http\View\Composers\SidebarComposer;
use App\Models\FundTransaction;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Observers\FundTransactionObserver;
use App\Observers\RequestObserver;
use App\Observers\UserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(NotificationServiceInterface::class, NotificationService::class);
        $this->app->bind(AllocationEngineServiceInterface::class, AllocationEngineService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FundTransaction::observe(app(FundTransactionObserver::class));
        RequestModel::observe(app(RequestObserver::class));
        User::observe(UserObserver::class);
        View::composer('*', SidebarComposer::class);

        config([
            'recipient.weekly_allowance_limit' => config('provider.recipient.weekly_allowance_limit', 400),
            'recipient.weekly_allowance_limit_min' => config('provider.recipient.weekly_allowance_limit_min', 1),
            'recipient.weekly_allowance_limit_max' => config('provider.recipient.weekly_allowance_limit_max', 100_000),
            'recipient.allowance_retry_delay_seconds' => config('provider.recipient.allowance_retry_delay_seconds', 60),
            'recipient.fund_retry_delay_seconds' => config('provider.recipient.fund_retry_delay_seconds', 60),
        ]);

        $this->configureRateLimiting();
    }

    /**
     * Named HTTP rate limiters for throttle:{name} middleware (see config/rate_limiting.php).
     * When rate_limiting.enabled is false, every limiter returns Limit::none().
     */
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
