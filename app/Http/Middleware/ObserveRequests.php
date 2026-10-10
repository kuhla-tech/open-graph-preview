<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Sentry\Unit;

class ObserveRequests
{
    public function handle(Request $request, Closure $next)
    {
        $start = hrtime(true);
        $status = 500;
        $response = null;
        try {
            $response = $next($request);
            $status = $response->getStatusCode();

            return $response;
        } finally {
            if (config('monitoring.enabled') && ! app()->runningUnitTests()) {
                $route = $request->route()?->uri() ?? 'unmatched';
                $route = trim(str_replace('{locale}', '', $route), '/') ?: '/';
                if (! in_array($route, ['/', 'preview', 'impressum', 'datenschutz', 'app.webmanifest'], true)) {
                    $route = 'other';
                }
                $attributes = ['route' => $route, 'method' => in_array($request->method(), ['GET', 'POST'], true) ? $request->method() : 'OTHER', 'status_code' => $status];
                $duration = (hrtime(true) - $start) / 1000000;
                try {
                    \Sentry\logger()->info('HTTP request completed', [], $attributes + ['duration_ms' => round($duration, 2)]);
                    \Sentry\traceMetrics()->count('opengraph.requests', 1, $attributes);
                    \Sentry\traceMetrics()->distribution('opengraph.duration', $duration, $attributes, Unit::millisecond());
                    if ($route === 'preview') {
                        \Sentry\traceMetrics()->count('opengraph.preview_requests', 1, $attributes);
                        if ($status >= 400) {
                            \Sentry\traceMetrics()->count('opengraph.preview_failures', 1, $attributes);
                        }
                        if ($response instanceof JsonResponse) {
                            $data = $response->getData(true);
                            foreach (['fail' => 'opengraph.check_failures', 'warn' => 'opengraph.check_warnings'] as $state => $metric) {
                                \Sentry\traceMetrics()->distribution($metric, count(array_filter($data['checks'] ?? [], fn ($check) => $check['status'] === $state)), $attributes);
                            }
                        }
                    }
                } catch (\Throwable $ignored) {
                    // Monitoring outages must not interrupt the preview.
                }
            }
        }
    }
}
