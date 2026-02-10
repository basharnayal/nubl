<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Helper function to get verified middleware based on config
$verifiedMiddleware = config('app.email_verification_enabled', true) 
    ? ['auth', 'email.verified'] 
    : ['auth'];

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(array_merge($verifiedMiddleware, ['redirect.by.role']))->name('dashboard');

Route::middleware($verifiedMiddleware)->group(function () {
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
Route::middleware(array_merge($verifiedMiddleware, ['role:admin']))->prefix('admin')->name('admin.')
    ->group(function () {

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');


    });

// Donor routes
Route::middleware(array_merge($verifiedMiddleware, ['role:donor']))->prefix('donor')->name('donor.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('donor.dashboard');
        })->name('dashboard');
    });

// Recipient routes
Route::middleware(array_merge($verifiedMiddleware, ['role:recipient']))->prefix('recipient')->name('recipient.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('recipient.dashboard');
        })->name('dashboard');
    });

// Provider routes
Route::middleware(array_merge($verifiedMiddleware, ['role:provider']))->prefix('provider')->name('provider.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return view('provider.dashboard');
        })->name('dashboard');
    });


    // Route::resource('jobs', JobController::class);
    // Route::resource('jobs', JobController::class)->except(['edit']);
    // Route::resource('jobs', JobController::class)->only(['index','show','create','store']);

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

require __DIR__.'/auth.php';
