<?php

namespace App\Http\Middleware;

use App\Support\LocalizedUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeLocale = $request->route('locale');
        $selected = $routeLocale ?? $request->query('lang');
        $saved = $request->session()->get('locale', config('app.locale', 'de'));
        $locale = in_array($selected, ['de', 'en'], true) ? $selected : (in_array($saved, ['de', 'en'], true) ? $saved : 'de');
        app()->setLocale($locale);
        $request->session()->put('locale', $locale);
        if ($request->isMethod('GET') && (! $routeLocale || $request->query->has('lang'))) {
            $path = $routeLocale ? str_replace('{locale}', '', $request->route()->uri()) : $request->path();
            $target = LocalizedUrl::to($path === '/' ? '/' : $path, $locale);
            $query = $request->query();
            unset($query['lang']);
            if ($query) {
                $target .= '?'.http_build_query($query);
            }

            return redirect()->to($target, $routeLocale || in_array($selected, ['de', 'en'], true) ? 301 : 302);
        }

        return $next($request);
    }
}
