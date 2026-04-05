<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Prevents browsers from caching HTML that embeds session CSRF tokens.
 *
 * - Guest auth routes (login, register, …): stale bfcache after session rotation → 419.
 * - Authenticated HTML (sidebar logout, forms): same class of failure when navigating back.
 *
 * Double POST from rapid clicks is mitigated globally in resources/js/form-submit-guard.js.
 */
class DisableHttpCacheForAuthForms
{
    /** @var list<string> */
    protected array $guestFormRouteNames = [
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

        if ($request->expectsJson()) {
            return $response;
        }

        foreach ($this->guestFormRouteNames as $name) {
            if ($request->routeIs($name)) {
                return $this->applyNoStore($response);
            }
        }

        if (Auth::check() && $this->isHtmlResponse($response)) {
            return $this->applyNoStore($response);
        }

        return $response;
    }

    private function isHtmlResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');

        return $contentType === '' || str_contains($contentType, 'text/html');
    }

    private function applyNoStore(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');

        return $response;
    }
}
