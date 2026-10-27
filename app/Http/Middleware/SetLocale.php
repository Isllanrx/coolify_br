<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale;

        if ($locale && isset(config('app.available_locales')[$locale])) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
