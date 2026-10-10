<?php

namespace App\Http\Middleware;

use App\Services\InstanceSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LoadInstanceSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        app(InstanceSettings::class)->apply();
        if ($request->is('admin', 'admin/*') && $request->hasSession()) {
            $locale = $request->session()->get('locale', config('app.locale'));
            app()->setLocale(in_array($locale, ['de', 'en'], true) ? $locale : 'de');
        }

        $response = $next($request);
        if ($request->is('admin', 'admin/*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
