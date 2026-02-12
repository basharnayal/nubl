<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Switch application language.
     */
    public function switch(Request $request, string $locale): RedirectResponse
    {
        // Validate locale
        $supportedLocales = config('app.supported_locales', ['en', 'ar']);
        
        if (!in_array($locale, $supportedLocales)) {
            $locale = config('app.fallback_locale', 'en');
        }

        // Set locale in session
        Session::put('locale', $locale);
        
        // Set application locale
        App::setLocale($locale);

        // Redirect back to previous page or home
        return redirect()->back();
    }
}
