<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Avoid caching GET pages that embed CSRF tokens (login, register, etc.).
 * If the browser restores a stale page from the back/forward cache while the
 * session token has rotated, POST returns 419 TokenMismatchException.
 */
class DisableHttpCacheForAuthForms
{
    /** @var list<string> */
    protected array $routeNames = [
        'login',
        'register',
        'register.provider',
        'password.request',
        'password.reset',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->isMethod('GET')) {
            return $response;
        }

        foreach ($this->routeNames as $name) {
            if ($request->routeIs($name)) {
                $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
                $response->headers->set('Pragma', 'no-cache');

                return $response;
            }
        }

        return $response;
    }
}
