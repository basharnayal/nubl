<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default (account.approved): blocks pending_approval / rejected from the main app; sends them to approval-pending.
 * Also blocks deactivated users (is_active=false).
 *
 * Pending-only routes (account.approved:pending_only): for approval-pending and resubmit — active users are
 * redirected to dashboard; everyone else passes through after auth + is_active checks.
 */
class EnsureAccountApproved
{
    public const MODE_PENDING_ONLY = 'pending_only';

    public function handle(Request $request, Closure $next, string $mode = 'default'): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => __('Your account has been deactivated.')]);
        }

        if ($mode === self::MODE_PENDING_ONLY) {
            if ($user->status === User::STATUS_ACTIVE) {
                return redirect()->route('dashboard');
            }

            return $next($request);
        }

        if (in_array($user->status, [User::STATUS_PENDING_APPROVAL, User::STATUS_REJECTED], true)) {
            return redirect()->route('approval.pending');
        }

        return $next($request);
    }
}
