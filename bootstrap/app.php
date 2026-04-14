<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // When using ngrok / Cloudflare Tunnel / Reflect, set TRUSTED_PROXIES=* in .env so
        // X-Forwarded-Host / X-Forwarded-Proto are honored. Then route(), asset(), and @vite
        // emit URLs for the public tunnel host instead of APP_URL (e.g. localhost), and
        // Alpine + bundled JS load so login/register fields are not stuck behind x-cloak.
        $trusted = env('TRUSTED_PROXIES');
        if ($trusted === '*') {
            $middleware->trustProxies(at: '*');
        } elseif (is_string($trusted) && $trusted !== '') {
            $middleware->trustProxies(at: array_map(trim(...), explode(',', $trusted)));
        }

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
