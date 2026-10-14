<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Langues prises en charge par l'API.
     */
    private const SUPPORTED = ['en', 'fr'];

    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolveLocale($request));

        return $next($request);
    }

    /**
     * Résout la locale depuis l'en-tête Accept-Language (ex. « fr », « en »)
     * avec repli sur la locale par défaut de l'application.
     */
    private function resolveLocale(Request $request): string
    {
        $header = $request->header('Accept-Language', '');

        foreach (explode(',', $header) as $segment) {
            $locale = strtolower(trim(explode(';', $segment)[0]));

            if (in_array($locale, self::SUPPORTED, true)) {
                return $locale;
            }
        }

        return config('app.locale');
    }
}
