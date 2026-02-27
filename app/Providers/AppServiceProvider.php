<?php

namespace App\Providers;

use App\Http\View\Composers\SidebarComposer;
use App\Models\FundTransaction;
use App\Observers\FundTransactionObserver;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FundTransaction::observe(FundTransactionObserver::class);
        View::composer('*', SidebarComposer::class);
    }
}
