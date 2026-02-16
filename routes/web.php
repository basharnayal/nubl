<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'redirect.by.role'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/test-flowbite', function () {
    return view('test-flowbite');
})->name('test-flowbite');

// Test page to check roles and redirect (Admin only - Remove in production)
Route::get('/test-roles', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    
    // Only admins can access this test page
    if (!auth()->user()->hasRole('admin')) {
        abort(403, 'Unauthorized');
    }
    
    $user = auth()->user();
    $roles = $user->roles->pluck('name')->toArray();
    
    return view('test-roles', compact('roles'));
})->middleware(['auth', 'role:admin'])->name('test-roles');




// Admin routes
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')
    ->group(function () {

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');


<<<<<<< Updated upstream
=======
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
>>>>>>> Stashed changes
    });

// Donor routes
Route::middleware(['auth', 'verified', 'role:donor'])->prefix('donor')->name('donor.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('donor.dashboard');
        })->name('dashboard');
    });

// Recipient routes
Route::middleware(['auth', 'verified', 'role:recipient'])->prefix('recipient')->name('recipient.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('recipient.dashboard');
        })->name('dashboard');
    });

// Provider routes
Route::middleware(['auth', 'verified', 'role:provider'])->prefix('provider')->name('provider.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('provider.dashboard');
        })->name('dashboard');
    });


<<<<<<< Updated upstream
    // Route::resource('jobs', JobController::class);
    // Route::resource('jobs', JobController::class)->except(['edit']);
    // Route::resource('jobs', JobController::class)->only(['index','show','create','store']);
=======

// General routes // 
// Pending approval: recipient or provider (blocked from dashboard by EnsureAccountApproved)
// Provider registration: GET allows guest + auth (auth with profile sees read-only)
Route::get('/register/provider', [ProviderRegistrationController::class, 'create'])->name('register.provider');
>>>>>>> Stashed changes

    // Route مؤقت - لا تحذفه للمشرفين فقط
Route::get('/make-me-admin', function () {
    $user = auth()->user();
    
    if (!$user) {
        return 'يجب تسجيل الدخول أولاً';
    }
    
    // التأكد من وجود الدور
    if (!\Spatie\Permission\Models\Role::where('name', 'admin')->exists()) {
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
    }
    
    $user->assignRole('admin');
    
    return 'تم تعيينك كـ admin بنجاح!';
})->middleware('auth');

require __DIR__ . '/auth.php';
