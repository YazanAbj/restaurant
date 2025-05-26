<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class ApiSetLocale
{
    public function handle($request, Closure $next)
    {
        // Get lang from header or query param
        $lang = $request->header('Accept-Language') ?? $request->query('lang');

        // Validate lang
        if ($lang && in_array($lang, ['en', 'ar'])) {
            App::setLocale($lang);
        } else {
            // fallback to default locale
            App::setLocale(config('app.locale'));
        }

        return $next($request);
    }
}
