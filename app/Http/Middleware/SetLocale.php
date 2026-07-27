<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locale precedence: user's saved language, then the session (POST /language),
 * then Accept-Language (any pt* maps to pt_BR), then en.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale($this->resolveLocale($request));

        return $next($request);
    }

    private function resolveLocale(Request $request): string
    {
        $locales = (array) config('sintoniza.locales', ['en']);

        $userLang = $request->user()?->language;
        if ($userLang && ($mapped = $this->mapLocale($userLang, $locales))) {
            return $mapped;
        }

        $sessionLang = $request->session()->get('language');
        if ($sessionLang && ($mapped = $this->mapLocale($sessionLang, $locales))) {
            return $mapped;
        }

        $preferred = $request->getPreferredLanguage(['en', 'es', 'pt']);
        if ($preferred === 'pt') {
            return in_array('pt_BR', $locales, true) ? 'pt_BR' : 'en';
        }

        return $preferred ?: 'en';
    }

    private function mapLocale(string $language, array $locales): ?string
    {
        $normalized = str_replace('-', '_', $language);

        if ($normalized === 'pt' || $normalized === 'pt_BR') {
            return in_array('pt_BR', $locales, true) ? 'pt_BR' : null;
        }

        return in_array($normalized, $locales, true) ? $normalized : null;
    }
}
