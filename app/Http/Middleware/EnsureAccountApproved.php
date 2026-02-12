<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks recipients with status=pending_approval from dashboard.
 * Redirects to approval-pending page.
 */
class EnsureAccountApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($user->status === User::STATUS_PENDING_APPROVAL) {
            return redirect()->route('approval.pending');
        }

        return $next($request);
    }
}
