<?php

/**
 * WEB ROUTES
 *
 * Middleware chain for protected routes:
 * auth → (email.verified if enabled) → account.approved → role-specific
 */

use App\Http\Controllers\Admin\AccountApprovalController;
use App\Http\Controllers\Admin\AdminFundTransactionController;
use App\Http\Controllers\Admin\AdminPaymentController;
use App\Http\Controllers\Admin\AllowanceSettingsController;
use App\Http\Controllers\Admin\FinancialOverviewController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\MaintenanceSettingsController;
use App\Http\Controllers\Admin\ProviderPayoutController;
use App\Http\Controllers\Admin\QrSettingsController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SummaryReportController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\ProviderRegistrationController;
use App\Http\Controllers\Auth\ResubmitApplicationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Provider\ProviderDashboardController;
use App\Http\Controllers\Provider\ProviderWalletController;
use App\Http\Controllers\Recipient\RecipientController;
use Illuminate\Support\Facades\Route;

// Auth middleware: phone OTP is primary verification; email remains optional
$authMiddleware = config('app.phone_verification_enabled', true)
    ? ['auth', 'phone.verified']
    : ['auth'];

Route::get('/', function () {
    return view('welcome');
});

// Locale switch (default: English, user can switch to Arabic)
Route::get('/locale/{locale}', function (string $locale) {
    $allowed = ['en', 'ar'];
    if (in_array($locale, $allowed)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('locale.switch');

// Legal (public)
Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/privacy-policy', 'legal.privacy')->name('legal.privacy');

// Redirect by role
Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(array_merge($authMiddleware, ['account.approved', 'redirect.by.role']))->name('dashboard');

Route::middleware(array_merge($authMiddleware, ['account.approved']))->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto'])
        ->middleware('throttle:profile_photo')
        ->name('profile.photo.upload');
    Route::patch('/profile/provider-business', [ProfileController::class, 'updateProviderBusiness'])
        ->name('profile.provider-business.update');
    Route::patch('/profile/provider-financial', [ProfileController::class, 'updateProviderFinancial'])
        ->name('profile.provider-financial.update');
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

        // Roles & permissions (Spatie)
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Admin Request Management (ECS-63)
        Route::get('/requests', [\App\Http\Controllers\Admin\AdminRequestController::class, 'index'])->name('requests.index');
        Route::put('/requests/{request}', [\App\Http\Controllers\Admin\AdminRequestController::class, 'update'])->name('requests.update');

        // FR-8.3: QR token TTL (admin)
        Route::get('/settings/qr', [QrSettingsController::class, 'edit'])->name('settings.qr.edit');
        Route::put('/settings/qr', [QrSettingsController::class, 'update'])->name('settings.qr.update');

        // FR-17.1: Weekly beneficiary allowance (scheduled next week)
        Route::get('/settings/allowances', [AllowanceSettingsController::class, 'edit'])->name('settings.allowances.edit');
        Route::put('/settings/allowances', [AllowanceSettingsController::class, 'update'])->name('settings.allowances.update');

        // Laravel built-in maintenance (artisan down / up + secret bypass)
        Route::get('/settings/maintenance', [MaintenanceSettingsController::class, 'edit'])->name('settings.maintenance.edit');
        Route::post('/settings/maintenance/enable', [MaintenanceSettingsController::class, 'enable'])
            ->middleware('throttle:6,1')
            ->name('settings.maintenance.enable');
        Route::post('/settings/maintenance/disable', [MaintenanceSettingsController::class, 'disable'])
            ->middleware('throttle:6,1')
            ->name('settings.maintenance.disable');

        // FR-24.1: Allocation engine pause/resume (global + per-provider)
        Route::get('/allocation/status', [\App\Http\Controllers\Admin\AdminAllocationController::class, 'status'])->name('allocation.status');
        Route::post('/allocation/pause', [\App\Http\Controllers\Admin\AdminAllocationController::class, 'pauseGlobal'])->name('allocation.pause');
        Route::post('/allocation/resume', [\App\Http\Controllers\Admin\AdminAllocationController::class, 'resumeGlobal'])->name('allocation.resume');
        Route::post('/allocation/providers/{provider}/pause', [\App\Http\Controllers\Admin\AdminAllocationController::class, 'pauseProvider'])->name('allocation.provider.pause');
        Route::post('/allocation/providers/{provider}/resume', [\App\Http\Controllers\Admin\AdminAllocationController::class, 'resumeProvider'])->name('allocation.provider.resume');

        // Fund management & payment monitoring (gateway vs internal ledger)
        Route::prefix('finances')->name('finances.')->group(function () {
            Route::get('/', [FinancialOverviewController::class, 'index'])->name('overview');
            Route::get('/payments/export', [AdminPaymentController::class, 'export'])->name('payments.export');
            Route::get('/payments/export-pdf', [AdminPaymentController::class, 'exportPdf'])->name('payments.export-pdf');
            Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
            Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
            Route::get('/fund-transactions/export', [AdminFundTransactionController::class, 'export'])->name('fund-transactions.export');
            Route::get('/fund-transactions/export-pdf', [AdminFundTransactionController::class, 'exportPdf'])->name('fund-transactions.export-pdf');
            Route::get('/fund-transactions', [AdminFundTransactionController::class, 'index'])->name('fund-transactions.index');
            Route::get('/fund-transactions/{fund_transaction}', [AdminFundTransactionController::class, 'show'])->name('fund-transactions.show');
            Route::get('/reports', [FinancialReportController::class, 'index'])->name('reports.index');
            // FR-19.1: auto-generated weekly / monthly summary reports
            Route::get('/summary-reports', [SummaryReportController::class, 'index'])->name('summary-reports.index');
            Route::get('/summary-reports/{summaryReport}/download', [SummaryReportController::class, 'download'])->name('summary-reports.download');

            Route::get('/provider-payouts', [ProviderPayoutController::class, 'index'])->name('provider-payouts.index');
            Route::get('/provider-payouts/{provider_payout}', [ProviderPayoutController::class, 'show'])->name('provider-payouts.show');
            Route::post('/provider-payouts/{provider_payout}/receipt', [ProviderPayoutController::class, 'storeReceipt'])->name('provider-payouts.receipt.store');
            Route::get('/provider-payouts/{provider_payout}/receipt-file', [ProviderPayoutController::class, 'receiptFile'])->name('provider-payouts.receipt.file');
            Route::post('/provider-payouts/{provider_payout}/confirm', [ProviderPayoutController::class, 'confirm'])->name('provider-payouts.confirm');
            Route::post('/provider-payouts/{provider_payout}/reject', [ProviderPayoutController::class, 'reject'])->name('provider-payouts.reject');
            Route::post('/provider-payouts/{provider_payout}/cancel', [ProviderPayoutController::class, 'cancel'])->name('provider-payouts.cancel');
        });

        // Audit Logs
        Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs.index');

        // Admin Menu Management
        Route::prefix('menus')->name('menus.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AdminMenuController::class, 'index'])->name('index');
            Route::get('/{provider}', [\App\Http\Controllers\Admin\AdminMenuController::class, 'show'])->name('show');
            Route::post('/{item}/toggle-block', [\App\Http\Controllers\Admin\AdminMenuController::class, 'toggleBlock'])->name('toggle-block');
        });
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

        Route::get('/dashboard', ProviderDashboardController::class)->name('dashboard');

        Route::get('/wallet', [ProviderWalletController::class, 'index'])->name('wallet.index');
        Route::get('/wallet/payouts/{provider_payout}/receipt', [ProviderWalletController::class, 'downloadReceipt'])
            ->name('wallet.payout-receipt');

        // Provider Menu Management (ECS-62)
        Route::resource('menu-items', \App\Http\Controllers\Provider\MenuItemController::class);

        // Provider Requests (ECS-63)
        Route::resource('requests', \App\Http\Controllers\Provider\ProviderRequestController::class)
            ->only(['index', 'show', 'update']);

        // Toggle Active
        Route::post('/profile/toggle-active', [\App\Http\Controllers\Provider\ProviderProfileController::class, 'toggleActive'])
            ->name('profile.toggle-active');

        Route::get('/profile/edit', [\App\Http\Controllers\Provider\ProviderProfileController::class, 'edit'])
            ->name('profile.edit');
        Route::put('/profile/edit', [\App\Http\Controllers\Provider\ProviderProfileController::class, 'update'])
            ->name('profile.update');

        // QR Redemption (ECS-111 & ECS-112)
        Route::get('/qr/scan', [\App\Http\Controllers\Provider\ProviderQrController::class, 'scan'])->name('qr.scan');
        Route::post('/qr/redeem', [\App\Http\Controllers\Provider\ProviderQrController::class, 'redeem'])->name('qr.redeem');

        Route::get('/redemptions/{redemption}/proof', [\App\Http\Controllers\Provider\ProviderProofController::class, 'index'])->name('proof.index');
        Route::post('/redemptions/{redemption}/proof', [\App\Http\Controllers\Provider\ProviderProofController::class, 'store'])->name('proof.store');
    });

// Recipient routes
Route::middleware(array_merge($authMiddleware, ['account.approved', 'role:recipient']))
    ->prefix('recipient')
    ->name('recipient.')
    ->group(function () {

        Route::get('/dashboard', [RecipientController::class, 'dashboard'])->name('dashboard');

        // Recipient Browsing (ECS-62)
        Route::get('/providers', [\App\Http\Controllers\Recipient\ProviderMenuController::class, 'index'])
            ->name('providers.index');

        Route::get('/providers/{provider}', [\App\Http\Controllers\Recipient\ProviderMenuController::class, 'show'])
            ->name('providers.show');

        Route::get('/providers/{provider}/menu', [RecipientController::class, 'providerMenu'])
            ->name('providers.menu');

        // Legacy URL: confirmation now lives on the request show page
        Route::get('requests/{id}/submitted', function (int $id) {
            return redirect()->route('recipient.requests.show', ['request' => $id], 301);
        })->whereNumber('id')->name('requests.submitted');
        Route::resource('requests', \App\Http\Controllers\Recipient\RecipientRequestController::class)
            ->only(['index', 'show', 'store']);
        Route::post('requests/cancel-throttle', [\App\Http\Controllers\Recipient\RecipientRequestController::class, 'cancelThrottle'])
            ->name('requests.cancel-throttle');
        Route::post('requests/{id}/cancel', [\App\Http\Controllers\Recipient\RecipientRequestController::class, 'cancel'])
            ->name('requests.cancel');
    });

// Donor routes
Route::middleware(array_merge($authMiddleware, ['account.approved', 'role:donor']))->prefix('donor')->name('donor.')
    ->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Donor\DonorDashboardController::class, 'index'])->name('dashboard');
        Route::get('/donations/new', [\App\Http\Controllers\Donor\DonationController::class, 'create'])->name('donations.new');
        Route::get('/donations', [\App\Http\Controllers\Donor\DonationController::class, 'index'])->name('donations.index');
        Route::get('/donations/{payment}/receipt', [\App\Http\Controllers\Donor\DonationController::class, 'receipt'])->name('donations.receipt');
        Route::post('/payments/initiate', [\App\Http\Controllers\Donor\DonationController::class, 'initiate'])
            ->middleware('throttle:donor_payments')
            ->name('payments.initiate');
        Route::get('/payments/success', [\App\Http\Controllers\PaymentCallbackController::class, 'success'])->name('payments.success');
        Route::get('/payments/failed', [\App\Http\Controllers\PaymentCallbackController::class, 'failed'])->name('payments.failed');
    });

// Notifications (auth required — for real-time polling)
Route::middleware(array_merge($authMiddleware, ['throttle:notifications']))->group(function () {
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

// Payment callback (no auth — MyFatoorah redirects the user's browser here).
// Rate-limited to prevent abuse (attackers flooding with random IDs to exhaust MyFatoorah API quota).
Route::get('/payments/callback', [\App\Http\Controllers\PaymentCallbackController::class, 'callback'])
    ->middleware('throttle:payments_gateway')
    ->name('payments.callback');
Route::get('/payments/error', [\App\Http\Controllers\PaymentCallbackController::class, 'error'])
    ->middleware('throttle:payments_gateway')
    ->name('payments.error');

// General routes //
// Pending approval: recipient or provider (blocked from dashboard by EnsureAccountApproved).
// Same middleware with pending_only: active users are redirected to dashboard from these URLs.
// Provider registration: GET allows guest + auth (auth with profile sees read-only)
Route::get('/register/provider', [ProviderRegistrationController::class, 'create'])
    ->middleware('throttle:registration')
    ->name('register.provider');

Route::middleware(array_merge($authMiddleware, ['account.approved:pending_only']))->group(function () {
    Route::get('/approval-pending', function () {
        return view('auth.approval-pending');
    })->name('approval.pending');

    Route::get('/application/resubmit', [ResubmitApplicationController::class, 'edit'])->name('application.resubmit.edit');
    Route::post('/application/resubmit', [ResubmitApplicationController::class, 'update'])
        ->middleware('throttle:application_resubmit')
        ->name('application.resubmit.update');
    Route::get('/application/my-file/{type}', [ResubmitApplicationController::class, 'serveFile'])->name('application.my-file');
});

// Test: debug roles (admin only - remove in production)
Route::get('/test-roles', function () {
    if (!auth()->user()->hasRole('admin')) {
        abort(403);
    }

    return view('test-roles', ['roles' => auth()->user()->roles->pluck('name')->toArray()]);
})->middleware(['auth', 'role:admin'])->name('test-roles');

// Dev helper: assign admin role (remove in production)
// Route::get('/make-me-admin', function () {
//     $user = auth()->user();
//     if (! \Spatie\Permission\Models\Role::where('name', 'admin')->exists()) {
//         \Spatie\Permission\Models\Role::create(['name' => 'admin']);
//     }
//     $user->assignRole('admin');

//     return 'تم تعيينك كـ admin بنجاح!';
// });

require __DIR__ . '/auth.php';
