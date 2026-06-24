<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public const SUPPORTED = ['en', 'id'];

    public function handle(Request $request, Closure $next)
    {
        // Priority: logged-in user's saved preference, then session, then default.
        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? config('app.locale');

        if (in_array($locale, self::SUPPORTED, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
