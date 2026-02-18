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
