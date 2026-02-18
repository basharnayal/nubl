<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneVerified
{
    /**
     * Handle an incoming request.
     * Redirect unverified users to /verify-phone.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.phone_verification_enabled', true)) {
            return $next($request);
        }

        if (! $request->user()) {
            return redirect()->route('login');
        }

        // Allow access to verify-phone and resend routes
        if ($request->routeIs('verification.phone*')) {
            return $next($request);
        }

        if (! $request->user()->hasVerifiedPhone()) {
            return redirect()->route('verification.phone');
        }

        return $next($request);
    }
}
