<?php

namespace App\Http\Controllers\Auth;

use App\Auth\PostAuthRedirector;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Login: after auth → PostAuthRedirector handles the full redirect chain.
 */
class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private AuditService $auditService,
        private PostAuthRedirector $postAuthRedirector,
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

        return $this->postAuthRedirector->redirect($user);
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
