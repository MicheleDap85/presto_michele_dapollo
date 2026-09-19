<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * @var list<string>
     */
    public const SUPPORTED_LOCALES = ['it', 'uk', 'es'];

    public const DEFAULT_LOCALE = 'it';

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $localeLanguage = session('locale', self::DEFAULT_LOCALE);

        if (! in_array($localeLanguage, self::SUPPORTED_LOCALES, true)) {
            $localeLanguage = self::DEFAULT_LOCALE;
        }

        App::setLocale($localeLanguage);

        return $next($request);
    }
}
