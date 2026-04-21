<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            if (! app()->environment('production')) {
                \Illuminate\Support\Facades\Route::middleware('web')
                    ->group(base_path('routes/testing.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
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

        // Apply mocked time on every request (no-op in production).
        $middleware->append(\App\Http\Middleware\ApplyMockedTime::class);

    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
