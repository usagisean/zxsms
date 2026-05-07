<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle($request, Closure $next)
    {
        $supported = array_keys(config('sms.locale.supported', ['zh_CN' => '中文', 'en' => 'English']));
        $default = config('sms.locale.default', 'zh_CN');

        $locale = $this->normalizeLocale($request->query('lang'), $supported);
        if ($locale && in_array($locale, $supported, true)) {
            $request->session()->put('locale', $locale);
        }

        $locale = $request->session()->get('locale', $default);
        if (! $request->session()->has('locale')) {
            $preferred = $this->preferredLocale($request, $supported);
            if ($preferred) {
                $locale = $preferred;
            }
        }
        if (! in_array($locale, $supported, true)) {
            $locale = $default;
        }

        App::setLocale($locale);
        return $next($request);
    }

    private function preferredLocale($request, array $supported)
    {
        $header = $request->header('Accept-Language', '');
        foreach (explode(',', $header) as $part) {
            $locale = trim(explode(';', $part)[0] ?? '');
            $locale = $this->normalizeLocale($locale, $supported);
            if ($locale) {
                return $locale;
            }
        }

        return null;
    }

    private function normalizeLocale($locale, array $supported)
    {
        if (! $locale) {
            return null;
        }

        $locale = str_replace('-', '_', trim((string) $locale));
        if (in_array($locale, $supported, true)) {
            return $locale;
        }

        $lower = strtolower($locale);
        foreach ($supported as $supportedLocale) {
            if (strtolower($supportedLocale) === $lower || strtolower(strtok($supportedLocale, '_')) === $lower) {
                return $supportedLocale;
            }
        }

        return null;
    }
}
