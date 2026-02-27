<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales.
     */
    protected array $locales = ['en', 'ar'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale', 'en'));

        if (in_array($locale, $this->locales)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
