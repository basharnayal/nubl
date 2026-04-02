<?php

namespace App\Providers;

use App\Contracts\NotificationServiceInterface;
use App\Http\Services\NotificationService;
use App\Http\View\Composers\SidebarComposer;
use App\Models\FundTransaction;
use App\Models\User;
use App\Observers\FundTransactionObserver;
use App\Observers\UserObserver;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FundTransaction::observe(app(FundTransactionObserver::class));
        User::observe(UserObserver::class);
        View::composer('*', SidebarComposer::class);

        config([
            'recipient.weekly_allowance_limit' => config('provider.recipient.weekly_allowance_limit', 400),
            'recipient.allowance_retry_delay_seconds' => config('provider.recipient.allowance_retry_delay_seconds', 60),
        ]);
    }
}
