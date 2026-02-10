<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If email verification is disabled, skip verification check
        if (!config('app.email_verification_enabled', true)) {
            return $next($request);
        }

        // If user is not authenticated, redirect to login
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // If email verification is enabled and user hasn't verified email, redirect to verification notice
        if (!$request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
