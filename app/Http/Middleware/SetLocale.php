<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Locale order of precedence: ?lang= → session → Accept-Language → English.
 * Register in bootstrap/app.php inside the 'web' middleware group.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->query('lang')
            ?? $request->session()->get('locale')
            ?? ($request->getPreferredLanguage(['en','ar']) ?: 'en');

        if (! in_array($locale, ['en','ar'], true)) {
            $locale = 'en';
        }

        App::setLocale($locale);
        $request->session()->put('locale', $locale);

        // Consumed by the layout for <html lang dir>.
        view()->share('locale', $locale);
        view()->share('dir', $locale === 'ar' ? 'rtl' : 'ltr');

        return $next($request);
    }
}
