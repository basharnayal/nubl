<?php

/*
|--------------------------------------------------------------------------
| Testing Routes  –  NEVER loaded in production
|--------------------------------------------------------------------------
|
| This file is only required by RouteServiceProvider (or bootstrap/app.php
| in Laravel 11+) when app()->environment() is NOT 'production'.
|
| Every route here is also protected by the TestingEnvironmentOnly
| middleware which performs a second environment check plus an optional
| shared-secret token check, providing defence-in-depth.
|
*/

use App\Http\Controllers\Testing\TimeController;
use App\Http\Middleware\TestingEnvironmentOnly;
use Illuminate\Support\Facades\Route;

Route::middleware(TestingEnvironmentOnly::class)
    ->prefix('_testing/time')
    ->name('testing.time.')
    ->group(function () {

        // Show current (possibly mocked) time
        Route::get('/', [TimeController::class, 'show'])->name('show');

        // Set the clock to an absolute datetime
        // Body: { "datetime": "2025-06-01 09:00:00" }
        Route::post('/set', [TimeController::class, 'set'])->name('set');

        // Advance the clock by a given duration
        // Body: { "days": 7 }  /  { "hours": 3, "minutes": 30 }  / etc.
        Route::post('/advance', [TimeController::class, 'advance'])->name('advance');
        Route::get('/advance', [TimeController::class, 'advance'])->name('advance.get');

        // Reset the clock back to real system time
        Route::post('/reset', [TimeController::class, 'reset'])->name('reset');
        Route::get('/reset', [TimeController::class, 'reset'])->name('reset.get');
    });