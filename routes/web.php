<?php

/**
 * WEB ROUTES
 *
 * Sections:
 *   1. Public                            (landing, locale, legal)
 *   2. Authenticated (any approved)      (dashboard, profile)
 *   3. Admin                             (role:admin)
 *   4. Provider                          (role:provider)
 *   5. Recipient                         (role:recipient)
 *   6. Donor                             (role:donor)
 *   7. Notifications                     (auth only — pending users included)
 *   8. Guest Donations                   (public)
 *   9. Payment Callbacks                 (public — gateway redirects)
 *  10. Pending Approval flow             (account.approved:pending_only)
 *
 * Middleware reference:
 *   auth -> phone.verified -> account.approved -> role:{role}
 *   (phone.verified is conditional via config('app.phone_verification_enabled'))
 *
 * Rate limiters (named, see config/rate_limiting.php + RateLimiterServiceProvider):
 *   registration, login, otp, password_reset, verification, sensitive_auth,
 *   payments_gateway, donor_payments, guest_donations, notifications,
 *   profile_photo, application_resubmit, file_downloads, guest_receipt
 */

use App\Http\Controllers\Admin\AccountApprovalController;
use App\Http\Controllers\Admin\AllocationController;
use App\Http\Controllers\Admin\AllowanceSettingsController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinancialOverviewController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\FundTransactionController;
use App\Http\Controllers\Admin\MaintenanceSettingsController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProviderPayoutController;
use App\Http\Controllers\Admin\QrSettingsController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SummaryReportController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Auth\ProviderRegistrationController;
use App\Http\Controllers\Auth\ResubmitApplicationController;
use App\Http\Controllers\Donor\DonationController;
use App\Http\Controllers\Donor\DonorDashboardController;
use App\Http\Controllers\GuestDonationController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\LandingPageFeedController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentCallbackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Provider\MenuItemController as ProviderMenuItemController;
use App\Http\Controllers\Provider\ProviderDashboardController;
use App\Http\Controllers\Provider\ProviderProfileController;
use App\Http\Controllers\Provider\ProviderProofController;
use App\Http\Controllers\Provider\ProviderQrController;
use App\Http\Controllers\Provider\ProviderRequestController;
use App\Http\Controllers\Provider\ProviderWalletController;
use App\Http\Controllers\Recipient\ProviderMenuController as RecipientProviderMenuController;
use App\Http\Controllers\Recipient\RecipientController;
use App\Http\Controllers\Recipient\RecipientRequestController;
use App\Http\Controllers\TopDonorsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Middleware stacks
|--------------------------------------------------------------------------
| Defined once and reused across role groups so the chain stays in sync
| and is trivial to adjust in one place.
*/

$authMiddleware = config('app.phone_verification_enabled', true)
    ? ['auth', 'phone.verified']
    : ['auth'];

$approvedMiddleware = array_merge($authMiddleware, ['account.approved']);
$adminMiddleware = array_merge($approvedMiddleware, ['role:admin']);
$providerMiddleware = array_merge($approvedMiddleware, ['role:provider']);
$recipientMiddleware = array_merge($approvedMiddleware, ['role:recipient']);
$donorMiddleware = array_merge($approvedMiddleware, ['role:donor']);
$pendingMiddleware = array_merge($authMiddleware, ['account.approved:pending_only']);

/*
|--------------------------------------------------------------------------
| 1. Public
|--------------------------------------------------------------------------
*/

Route::get('/', LandingPageController::class)->name('home');

Route::get('/landing/feed', LandingPageFeedController::class)
    ->middleware('throttle:120,1')
    ->name('landing.feed');

// Locale switch (default: English; user can switch to Arabic).
// Constraint prevents arbitrary values from being persisted to session.
Route::get('/locale/{locale}', [LanguageController::class, 'switch'])
    ->where('locale', 'en|ar')
    ->name('locale.switch');

// Legal
Route::view('/terms', 'legal.terms')->name('legal.terms');
Route::view('/privacy-policy', 'legal.privacy')->name('legal.privacy');

// Top Donors (سواعد نُبل)
Route::get('/top-donors', TopDonorsController::class)->name('top-donors.index');

/*
|--------------------------------------------------------------------------
| 2. Authenticated (any approved role)
|--------------------------------------------------------------------------
*/

// Role-aware redirect to the right dashboard
Route::view('/dashboard', 'dashboard')
    ->middleware(array_merge($approvedMiddleware, ['redirect.by.role']))
    ->name('dashboard');

Route::middleware($approvedMiddleware)->group(function () {
    Route::controller(ProfileController::class)->prefix('profile')->name('profile.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::patch('/', 'update')->name('update');
        Route::post('/photo', 'uploadPhoto')
            ->middleware('throttle:profile_photo')
            ->name('photo.upload');
        Route::patch('/provider-business', 'updateProviderBusiness')->name('provider-business.update');
        Route::patch('/provider-financial', 'updateProviderFinancial')->name('provider-financial.update');
        Route::delete('/', 'destroy')->name('destroy');
    });
});

/*
|--------------------------------------------------------------------------
| 3. Admin
|--------------------------------------------------------------------------
*/

Route::middleware($adminMiddleware)->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Account approval flow (pending users awaiting admin review) — gated by accounts.approve
    Route::middleware('permission:accounts.approve')->controller(AccountApprovalController::class)->group(function () {
        Route::get('/users/pending', 'index')->name('users.pending');
        Route::post('/users/{user}/approve', 'approve')->whereNumber('user')->name('users.approve');
        Route::get('/users/{user}/reject', 'showRejectForm')->whereNumber('user')->name('users.reject.form');
        Route::post('/users/{user}/reject', 'reject')->whereNumber('user')->name('users.reject');
        Route::get('/users/{user}/application', 'showApplication')->whereNumber('user')->name('users.application');
        Route::get('/users/{user}/file/{type}', 'serveFile')
            ->whereNumber('user')
            ->middleware('throttle:file_downloads')
            ->name('users.file');
    });

    // User Management (CRUD + deactivate/reactivate) — separate from approval flow
    Route::controller(UserManagementController::class)->prefix('manage/users')->name('manage.users.')->group(function () {
        Route::middleware('permission:users.read')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{user}', 'show')->whereNumber('user')->name('show');
        });
        Route::middleware('permission:users.create')->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
        });
        Route::middleware('permission:users.update')->group(function () {
            Route::get('/{user}/edit', 'edit')->whereNumber('user')->name('edit');
            Route::put('/{user}', 'update')->whereNumber('user')->name('update');
        });
        Route::delete('/{user}', 'destroy')->whereNumber('user')->name('destroy')
            ->middleware('permission:users.delete');
        Route::post('/{user}/deactivate', 'deactivate')->whereNumber('user')->name('deactivate')
            ->middleware('permission:users.deactivate');
        Route::post('/{user}/reactivate', 'reactivate')->whereNumber('user')->name('reactivate')
            ->middleware('permission:users.reactivate');
    });

    // Roles & permissions (Spatie) — gated by the roles.manage permission on top of role:admin (defense in depth)
    Route::middleware('permission:roles.manage')
        ->controller(RoleController::class)->prefix('roles')->name('roles.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{role}/edit', 'edit')->whereNumber('role')->name('edit');
        Route::put('/{role}', 'update')->whereNumber('role')->name('update');
        Route::delete('/{role}', 'destroy')->whereNumber('role')->name('destroy');
    });

    // Admin Request Management (ECS-63)
    Route::controller(RequestController::class)->prefix('requests')->name('requests.')->group(function () {
        Route::get('/', 'index')->name('index')->middleware('permission:requests.review');
        Route::put('/{request}', 'update')->whereNumber('request')->name('update');
    });

    // FR-8.3: QR token TTL (admin)
    Route::controller(QrSettingsController::class)->prefix('settings/qr')->name('settings.qr.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
    });

    // FR-17.1: Weekly beneficiary allowance (scheduled next week)
    Route::controller(AllowanceSettingsController::class)->prefix('settings/allowances')->name('settings.allowances.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::put('/', 'update')->name('update');
    });

    // Laravel built-in maintenance (artisan down / up + secret bypass)
    Route::middleware('permission:maintenance.manage')
        ->controller(MaintenanceSettingsController::class)->prefix('settings/maintenance')->name('settings.maintenance.')->group(function () {
        Route::get('/', 'edit')->name('edit');
        Route::post('/enable', 'enable')->middleware('throttle:6,1')->name('enable');
        Route::post('/disable', 'disable')->middleware('throttle:6,1')->name('disable');
    });

    // FR-24.1: Allocation engine pause/resume (global + per-provider)
    Route::controller(AllocationController::class)->prefix('allocation')->name('allocation.')->group(function () {
        Route::get('/status', 'status')->name('status');
        Route::post('/pause', 'pauseGlobal')->name('pause')->middleware('permission:allocation.pause_global');
        Route::post('/resume', 'resumeGlobal')->name('resume')->middleware('permission:allocation.pause_global');
        Route::post('/providers/{provider}/pause', 'pauseProvider')->whereNumber('provider')->name('provider.pause')
            ->middleware('permission:allocation.pause_per_provider');
        Route::post('/providers/{provider}/resume', 'resumeProvider')->whereNumber('provider')->name('provider.resume')
            ->middleware('permission:allocation.pause_per_provider');
    });

    // Fund management & payment monitoring (gateway vs internal ledger)
    Route::middleware('permission:finance.manage')->prefix('finances')->name('finances.')->group(function () {
        Route::get('/', [FinancialOverviewController::class, 'index'])->name('overview');

        Route::controller(PaymentController::class)->prefix('payments')->name('payments.')->group(function () {
            Route::get('/export', 'export')->name('export')->middleware('permission:reports.export_csv');
            Route::get('/export-pdf', 'exportPdf')->name('export-pdf')->middleware('permission:reports.export_pdf');
            Route::get('/', 'index')->name('index');
            Route::get('/{payment}', 'show')->whereNumber('payment')->name('show');
        });

        Route::controller(FundTransactionController::class)->prefix('fund-transactions')->name('fund-transactions.')->group(function () {
            Route::get('/export', 'export')->name('export')->middleware('permission:reports.export_csv');
            Route::get('/export-pdf', 'exportPdf')->name('export-pdf')->middleware('permission:reports.export_pdf');
            Route::get('/', 'index')->name('index');
            Route::get('/{fund_transaction}', 'show')->whereNumber('fund_transaction')->name('show');
        });

        Route::get('/reports', [FinancialReportController::class, 'index'])->name('reports.index');

        // FR-19.1: auto-generated weekly / monthly summary reports
        Route::controller(SummaryReportController::class)->prefix('summary-reports')->name('summary-reports.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{summaryReport}/download', 'download')
                ->whereNumber('summaryReport')
                ->middleware('throttle:file_downloads')
                ->name('download');
        });

        Route::controller(ProviderPayoutController::class)->prefix('provider-payouts')->name('provider-payouts.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{provider_payout}', 'show')->whereNumber('provider_payout')->name('show');
            Route::post('/{provider_payout}/receipt', 'storeReceipt')->whereNumber('provider_payout')->name('receipt.store');
            Route::get('/{provider_payout}/receipt-file', 'receiptFile')
                ->whereNumber('provider_payout')
                ->middleware('throttle:file_downloads')
                ->name('receipt.file');
            Route::post('/{provider_payout}/confirm', 'confirm')->whereNumber('provider_payout')->name('confirm');
            Route::post('/{provider_payout}/reject', 'reject')->whereNumber('provider_payout')->name('reject');
            Route::post('/{provider_payout}/cancel', 'cancel')->whereNumber('provider_payout')->name('cancel');
        });
    });

    // Audit Logs
    Route::middleware('permission:audit_logs.view')->group(function () {
        Route::get('/audit-logs/export', [AuditLogController::class, 'export'])
            ->middleware('permission:reports.export_csv')->name('audit-logs.export');
        Route::get('/audit-logs/export-pdf', [AuditLogController::class, 'exportPdf'])
            ->middleware('permission:reports.export_pdf')->name('audit-logs.export-pdf');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{log}/verify', [AuditLogController::class, 'verify'])->name('audit-logs.verify');
    });

    // Admin Menu Management
    Route::controller(MenuController::class)->prefix('menus')->name('menus.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{provider}', 'show')->whereNumber('provider')->name('show');
        Route::post('/{item}/toggle-block', 'toggleBlock')->whereNumber('item')->name('toggle-block');
    });
});

/*
|--------------------------------------------------------------------------
| 4. Provider
|--------------------------------------------------------------------------
*/

Route::middleware($providerMiddleware)->prefix('provider')->name('provider.')->group(function () {

    // Pending providers must still view their application — opt out of the approval gate
    Route::get('/application', [ProviderRegistrationController::class, 'showApplication'])
        ->withoutMiddleware('account.approved')
        ->name('application');

    Route::get('/dashboard', ProviderDashboardController::class)->name('dashboard');

    Route::get('/wallet', [ProviderWalletController::class, 'index'])->name('wallet.index');
    Route::get('/wallet/payouts/{provider_payout}/receipt', [ProviderWalletController::class, 'downloadReceipt'])
        ->whereNumber('provider_payout')
        ->middleware('throttle:file_downloads')
        ->name('wallet.payout-receipt');

    // Provider Menu Management (ECS-62)
    Route::resource('menu-items', ProviderMenuItemController::class);

    // Provider Requests (ECS-63)
    Route::resource('requests', ProviderRequestController::class)->only(['index', 'show', 'update']);

    // Profile
    Route::controller(ProviderProfileController::class)->prefix('profile')->name('profile.')->group(function () {
        Route::post('/toggle-active', 'toggleActive')->name('toggle-active');
        Route::get('/edit', 'edit')->name('edit');
        Route::put('/edit', 'update')->name('update');
    });

    // QR Redemption (ECS-111 & ECS-112)
    Route::controller(ProviderQrController::class)->prefix('qr')->name('qr.')->group(function () {
        Route::get('/scan', 'scan')->name('scan');
        Route::post('/redeem', 'redeem')->name('redeem');
    });

    Route::controller(ProviderProofController::class)->prefix('redemptions/{redemption}')->name('proof.')->group(function () {
        Route::get('/proof', 'index')->whereNumber('redemption')->name('index');
        Route::post('/proof', 'store')->whereNumber('redemption')->name('store');
    });
});

/*
|--------------------------------------------------------------------------
| 5. Recipient
|--------------------------------------------------------------------------
*/

Route::middleware($recipientMiddleware)->prefix('recipient')->name('recipient.')->group(function () {

    Route::get('/dashboard', [RecipientController::class, 'dashboard'])->name('dashboard');

    // Recipient Browsing (ECS-62)
    Route::controller(RecipientProviderMenuController::class)->prefix('providers')->name('providers.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{provider}', 'show')->whereNumber('provider')->name('show');
    });

    Route::get('/providers/{provider}/menu', [RecipientController::class, 'providerMenu'])
        ->whereNumber('provider')
        ->name('providers.menu');

    // Legacy URL: confirmation now lives on the request show page
    Route::get('requests/{id}/submitted', function (int $id) {
        return redirect()->route('recipient.requests.show', ['request' => $id], 301);
    })->whereNumber('id')->name('requests.submitted');

    Route::resource('requests', RecipientRequestController::class)->only(['index', 'show', 'store']);
    Route::post('requests/cancel-throttle', [RecipientRequestController::class, 'cancelThrottle'])
        ->name('requests.cancel-throttle');
    Route::post('requests/{id}/cancel', [RecipientRequestController::class, 'cancel'])
        ->whereNumber('id')
        ->name('requests.cancel');

    Route::get('/chart-data', [RecipientController::class, 'chartDataApi'])->name('chart-data.api');
});

/*
|--------------------------------------------------------------------------
| 6. Donor
|--------------------------------------------------------------------------
*/

Route::middleware($donorMiddleware)->prefix('donor')->name('donor.')->group(function () {
    Route::get('/dashboard', [DonorDashboardController::class, 'index'])->name('dashboard');

    Route::controller(DonationController::class)->group(function () {
        Route::get('/donations/new', 'create')->name('donations.new');
        Route::get('/donations', 'index')->name('donations.index');
        Route::get('/donations/{payment}/receipt', 'receipt')
            ->whereNumber('payment')
            ->middleware('throttle:file_downloads')
            ->name('donations.receipt');
        Route::post('/payments/initiate', 'initiate')
            ->middleware('throttle:donor_payments')
            ->name('payments.initiate');
    });

    Route::controller(PaymentCallbackController::class)->prefix('payments')->name('payments.')->group(function () {
        Route::get('/success', 'success')->name('success');
        Route::get('/failed', 'failed')->name('failed');
    });
});

/*
|--------------------------------------------------------------------------
| 7. Notifications (auth required, real-time polling — pending users included)
|--------------------------------------------------------------------------
| NOTE: this group intentionally lacks `account.approved` so users awaiting
| approval can still receive notifications about their application status.
*/

Route::middleware(array_merge($authMiddleware, ['throttle:notifications']))->group(function () {
    Route::controller(NotificationController::class)->prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/{notification}/read', 'markAsRead')
            ->where('notification', '[0-9a-fA-F\-]{36}')
            ->name('read');
        Route::post('/read-all', 'markAllAsRead')->name('read-all');
    });
});

/*
|--------------------------------------------------------------------------
| 8. Guest Quick Donation (public, no auth required)
|--------------------------------------------------------------------------
*/

Route::controller(GuestDonationController::class)->prefix('donate')->name('guest.donation.')->group(function () {
    Route::post('/initiate', 'initiate')
        ->middleware('throttle:guest_donations')
        ->name('initiate');
    Route::get('/success', 'success')->name('success');
    Route::get('/receipt/{token}', 'receipt')
        ->where('token', '[0-9a-f\-]{36}')
        ->middleware('throttle:guest_receipt')
        ->name('receipt');
    Route::get('/failed', 'failed')->name('failed');
});

/*
|--------------------------------------------------------------------------
| 9. Payment Callbacks (public — MyFatoorah redirects browser here)
|--------------------------------------------------------------------------
| Rate-limited to prevent attackers flooding with random IDs to exhaust
| the MyFatoorah API quota.
*/

Route::middleware('throttle:payments_gateway')->group(function () {
    Route::get('/payments/callback', [PaymentCallbackController::class, 'callback'])->name('payments.callback');
    Route::get('/payments/error', [PaymentCallbackController::class, 'error'])->name('payments.error');
});

/*
|--------------------------------------------------------------------------
| 10. Pending Approval flow + Provider Registration (guest GET)
|--------------------------------------------------------------------------
| Provider registration GET: allows guest + auth (auth users with profile see read-only).
| Other routes here use `account.approved:pending_only` — active users are redirected
| to their dashboard from these URLs.
*/

Route::get('/register/provider', [ProviderRegistrationController::class, 'create'])
    ->middleware('throttle:registration')
    ->name('register.provider');

Route::middleware($pendingMiddleware)->group(function () {
    Route::view('/approval-pending', 'auth.approval-pending')->name('approval.pending');

    Route::controller(ResubmitApplicationController::class)->prefix('application')->name('application.')->group(function () {
        Route::get('/resubmit', 'edit')->name('resubmit.edit');
        Route::post('/resubmit', 'update')
            ->middleware('throttle:application_resubmit')
            ->name('resubmit.update');
        Route::get('/my-file/{type}', 'serveFile')
            ->middleware('throttle:file_downloads')
            ->name('my-file');
    });
});

require __DIR__.'/auth.php';
