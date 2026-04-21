<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TestingEnvironmentOnly
 *
 * Guards every route inside routes/testing.php.
 * Any request that reaches this middleware in a production environment
 * receives a 404 – the testing routes appear to not exist at all.
 *
 * A second, optional layer of protection is a shared secret token.
 * Set TESTING_TIME_TOKEN=<some-long-random-string> in your .env (or
 * CI environment variables) and pass it as the X-Testing-Token header
 * from your automation tool.  Leave the env var empty to disable token
 * checking (useful for purely local development).
 */
class TestingEnvironmentOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        // ── Layer 1: environment check ────────────────────────────────────
        // Routes are only loadable from non-production environments.
        // The RouteServiceProvider also guards this at the routing level,
        // but defence-in-depth means we check here too.
        if (app()->environment('production')) {
            abort(404);
        }

        // ── Layer 2 (optional): shared-secret token check ─────────────────
        // Configure TESTING_TIME_TOKEN in your .env / CI secrets.
        // If the env var is set, every request must supply a matching
        // X-Testing-Token header; otherwise the request is rejected.
        $configuredToken = config('app.testing_time_token');

        if (!empty($configuredToken)) {
            $providedToken = $request->header('X-Testing-Token');

            if (!hash_equals($configuredToken, (string) $providedToken)) {
                abort(403, 'Invalid or missing X-Testing-Token header.');
            }
        }

        return $next($request);
    }
}