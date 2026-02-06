<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectByRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // If already on a role-specific dashboard, don't redirect
        if ($request->routeIs('admin.dashboard') || 
            $request->routeIs('donor.dashboard') || 
            $request->routeIs('recipient.dashboard') || 
            $request->routeIs('provider.dashboard')) {
            return $next($request);
        }

        // Redirect based on role
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