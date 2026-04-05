<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Services\AuditService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Login: after auth → email verify (if enabled) → pending? approval-pending → dashboard
 */
class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => __('Your account has been deactivated. Please contact support.'),
            ]);
        }
        $this->auditService->log('auth', 'login', [
            'user_id' => $user->id,
            'method' => 'password',
        ], $user->id);

        if (config('app.phone_verification_enabled', true) && ! $user->hasVerifiedPhone()) {
            return redirect()->route('verification.phone');
        }
        if (config('app.email_verification_enabled', true) && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }
        if (in_array($user->status, [User::STATUS_PENDING_APPROVAL, User::STATUS_REJECTED])) {
            return redirect()->route('approval.pending');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $userId = $request->user()?->id;

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($userId !== null) {
            $this->auditService->log('auth', 'logout', [
                'user_id' => $userId,
            ], $userId);
        }

        return redirect('/');
    }
}
