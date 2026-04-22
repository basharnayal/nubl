<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function () {
            Route::middleware('web')
                 ->group(base_path('routes/web.php'));

            if (! app()->environment('production')) {
                Route::middleware('api')
                     ->group(base_path('routes/testing.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\ApplyMockedTime::class);

        // Plain XSRF-TOKEN in the browser; required for JS CSRF + axios/fetch headers.
        $middleware->encryptCookies(except: [
            'XSRF-TOKEN',
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\DisableHttpCacheForAuthForms::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'redirect.by.role' => \App\Http\Middleware\RedirectByRole::class,
            'email.verified' => \App\Http\Middleware\EnsureEmailVerified::class,
            'phone.verified' => \App\Http\Middleware\EnsurePhoneVerified::class,
            'account.approved' => \App\Http\Middleware\EnsureAccountApproved::class,
        ]);
    
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
