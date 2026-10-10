<?php

namespace App\Support;

class LocalizedUrl
{
    public static function to(string $path = '/', ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $locale = in_array($locale, ['de', 'en'], true) ? $locale : 'de';

        return rtrim(config('app.url'), '/').'/'.$locale.'/'.ltrim($path, '/');
    }

    public static function current(string $locale): string
    {
        $path = str_replace('{locale}', '', request()->route()?->uri() ?? '/');

        return self::to($path, $locale);
    }
}
