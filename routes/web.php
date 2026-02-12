<?php

/**
 * WEB ROUTES
 *
 * Middleware chain for protected routes:
 * auth → (email.verified if enabled) → account.approved → role-specific
 */

use App\Http\Controllers\Auth\ProviderRegistrationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Auth middleware: requires email verification if EMAIL_VERIFICATION_ENABLED=true
$authMiddleware = config('app.email_verification_enabled', true)
    ? ['auth', 'email.verified']
    : ['auth'];

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(array_merge($authMiddleware, ['account.approved', 'redirect.by.role']))->name('dashboard');

Route::middleware(array_merge($authMiddleware, ['account.approved']))->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/test-flowbite', function () {
    return view('test-flowbite');
})->name('test-flowbite');

// Pending approval: recipient or provider (blocked from dashboard by EnsureAccountApproved)
Route::get('/approval-pending', function () {
    return view('auth.approval-pending');
})->middleware('auth')->name('approval.pending');

// Provider registration: GET allows guest + auth (auth with profile sees read-only)
Route::get('/register/provider', [ProviderRegistrationController::class, 'create'])->name('register.provider');

// Provider application view (read-only, for pending providers)
Route::get('/provider/application', [ProviderRegistrationController::class, 'showApplication'])
    ->middleware(['auth', 'role:provider'])
    ->name('provider.application');

// Test: debug roles (admin only - remove in production)
Route::get('/test-roles', function () {
    if (!auth()->user()->hasRole('admin')) {
        abort(403);
    }
    return view('test-roles', ['roles' => auth()->user()->roles->pluck('name')->toArray()]);
})->middleware(['auth', 'role:admin'])->name('test-roles');

// Role-specific dashboards
Route::middleware(array_merge($authMiddleware, ['account.approved', 'role:admin']))->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');
    });

Route::middleware(array_merge($authMiddleware, ['account.approved', 'role:donor']))->prefix('donor')->name('donor.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('donor.dashboard');
        })->name('dashboard');
    });

// Recipient routes
Route::middleware(array_merge($authMiddleware, ['account.approved', 'role:recipient']))->prefix('recipient')->name('recipient.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('recipient.dashboard');
        })->name('dashboard');
    });

// Provider routes
Route::middleware(array_merge($authMiddleware, ['account.approved', 'role:provider']))->prefix('provider')->name('provider.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('provider.dashboard');
        })->name('dashboard');
    });


// Dev helper: assign admin role (remove in production)
Route::get('/make-me-admin', function () {
    $user = auth()->user();
    if (!\Spatie\Permission\Models\Role::where('name', 'admin')->exists()) {
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
    }
    $user->assignRole('admin');
    return 'تم تعيينك كـ admin بنجاح!';
})->middleware(['auth', 'account.approved']);




require __DIR__.'/auth.php';
