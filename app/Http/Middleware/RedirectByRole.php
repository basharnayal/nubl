<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirects /dashboard to role-specific dashboard (admin, donor, recipient, provider).
 */
class RedirectByRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        if ($request->routeIs('*.dashboard')) {
            return $next($request);
        }

        // Redirect to role dashboard
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->hasRole('donor')) {
            return redirect()->route('donor.dashboard');
        } elseif ($user->hasRole('recipient')) {
            return redirect()->route('recipient.dashboard');
        } elseif ($user->hasRole('provider')) {
            return redirect()->route('provider.dashboard');
        }

        // If user has no role, show default dashboard
        return $next($request);
    }
}