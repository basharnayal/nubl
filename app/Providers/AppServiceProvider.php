<?php

namespace App\Providers;

use App\Contracts\AllocationEngineServiceInterface;
use App\Contracts\NotificationServiceInterface;
use App\View\Composers\SidebarComposer;
use App\Models\FundTransaction;
use App\Models\Request as RequestModel;
use App\Models\User;
use App\Observers\FundTransactionObserver;
use App\Observers\RequestObserver;
use App\Observers\UserObserver;
use App\Services\AllocationEngineService;
use App\Services\NotificationService;
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
        View::composer([
            'components.app-partials.main-sidebar',
            'components.app-partials.sidebar-panel',
        ], SidebarComposer::class);

        config([
            'recipient.weekly_allowance_limit' => config('provider.recipient.weekly_allowance_limit', 400),
            'recipient.weekly_allowance_limit_min' => config('provider.recipient.weekly_allowance_limit_min', 1),
            'recipient.weekly_allowance_limit_max' => config('provider.recipient.weekly_allowance_limit_max', 100_000),
            'recipient.allowance_retry_delay_seconds' => config('provider.recipient.allowance_retry_delay_seconds', 60),
            'recipient.fund_retry_delay_seconds' => config('provider.recipient.fund_retry_delay_seconds', 60),
        ]);
    }
}
