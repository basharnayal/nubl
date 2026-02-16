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
use App\Http\Controllers\Admin\AccountApprovalController;
use App\Http\Controllers\Recipient\RecipientController;
use App\Http\Controllers\Admin\UserManagementController;

// Auth middleware: requires email verification if EMAIL_VERIFICATION_ENABLED=true
$authMiddleware = config('app.email_verification_enabled', true)
    ? ['auth', 'email.verified']
    : ['auth'];

Route::get('/', function () {
    return view('welcome');
});

// Redirect by role
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(array_merge($authMiddleware, ['account.approved', 'redirect.by.role']))->name('dashboard');

Route::middleware(array_merge($authMiddleware, ['account.approved']))->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});






// $$ Role-specific dashboards $$ 
// Admin routes 
Route::middleware(array_merge($authMiddleware, ['account.approved', 'role:admin']))->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        Route::get('/users/pending', [AccountApprovalController::class, 'index'])->name('users.pending');
        Route::post('/users/{user}/approve', [AccountApprovalController::class, 'approve'])->name('users.approve');
        Route::get('/users/{user}/reject', [AccountApprovalController::class, 'showRejectForm'])->name('users.reject.form');
        Route::post('/users/{user}/reject', [AccountApprovalController::class, 'reject'])->name('users.reject');
        Route::get('/users/{user}/application', [AccountApprovalController::class, 'showApplication'])->name('users.application');
        Route::get('/users/{user}/file/{type}', [AccountApprovalController::class, 'serveFile'])->name('users.file');

        // User Management (CRUD + deactivate/reactivate) - separate from approval flow
        Route::get('/manage/users', [UserManagementController::class, 'index'])->name('manage.users.index');
        Route::get('/manage/users/create', [UserManagementController::class, 'create'])->name('manage.users.create');
        Route::post('/manage/users', [UserManagementController::class, 'store'])->name('manage.users.store');
        Route::get('/manage/users/{user}', [UserManagementController::class, 'show'])->name('manage.users.show');
        Route::get('/manage/users/{user}/edit', [UserManagementController::class, 'edit'])->name('manage.users.edit');
        Route::put('/manage/users/{user}', [UserManagementController::class, 'update'])->name('manage.users.update');
        Route::delete('/manage/users/{user}', [UserManagementController::class, 'destroy'])->name('manage.users.destroy');
        Route::post('/manage/users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('manage.users.deactivate');
        Route::post('/manage/users/{user}/reactivate', [UserManagementController::class, 'reactivate'])->name('manage.users.reactivate');
    });

// Provider routes
Route::middleware(array_merge($authMiddleware, ['account.approved', 'role:provider']))
    ->prefix('provider')
    ->name('provider.')
    ->group(function () {

        // Pending providers must still view their application
        Route::get('/application', [\App\Http\Controllers\Auth\ProviderRegistrationController::class, 'showApplication'])
            ->withoutMiddleware('account.approved')
            ->name('application');

        Route::get('/dashboard', fn() => view('provider.dashboard'))->name('dashboard');

        // Provider Menu Management (ECS-62)
        Route::resource('menu-items', \App\Http\Controllers\Provider\MenuItemController::class);
    });

// Recipient routes
Route::middleware(array_merge($authMiddleware, ['account.approved', 'role:recipient']))
    ->prefix('recipient')
    ->name('recipient.')
    ->group(function () {

        Route::get('/dashboard', fn() => view('recipient.dashboard'))->name('dashboard');

        // Recipient Browsing (ECS-62)
        Route::get('/providers', [\App\Http\Controllers\Recipient\ProviderMenuController::class, 'index'])
            ->name('providers.index');

        Route::get('/providers/{provider}', [\App\Http\Controllers\Recipient\ProviderMenuController::class, 'show'])
            ->name('providers.show');
    });

// Donor routes
Route::middleware(array_merge($authMiddleware, ['account.approved', 'role:donor']))->prefix('donor')->name('donor.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('donor.dashboard');
        })->name('dashboard');
    });



// General routes // 
// Pending approval: recipient or provider (blocked from dashboard by EnsureAccountApproved)
// Provider registration: GET allows guest + auth (auth with profile sees read-only)
Route::get('/register/provider', [ProviderRegistrationController::class, 'create'])->name('register.provider');

Route::get('/approval-pending', function () {
    return view('auth.approval-pending');
})->middleware('auth')->name('approval.pending');



// Test: debug roles (admin only - remove in production)
Route::get('/test-roles', function () {
    if (!auth()->user()->hasRole('admin')) {
        abort(403);
    }
    return view('test-roles', ['roles' => auth()->user()->roles->pluck('name')->toArray()]);
})->middleware(['auth', 'role:admin'])->name('test-roles');

Route::get('/test-flowbite', function () {
    return view('test-flowbite');
})->name('test-flowbite');

// Dev helper: assign admin role (remove in production)
Route::get('/make-me-admin', function () {
    $user = auth()->user();
    if (!\Spatie\Permission\Models\Role::where('name', 'admin')->exists()) {
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
    }
    $user->assignRole('admin');
    return 'تم تعيينك كـ admin بنجاح!';
})->middleware(['auth', 'account.approved']);




require __DIR__ . '/auth.php';
