<?php

use App\Monitoring\TelemetryPrivacy;

return [
    'dsn' => env('MONITORING_ENABLED', env('APP_ENV') === 'production') ? env('SENTRY_DSN') : null,
    'release' => env('SENTRY_RELEASE', 'opengraph-works@1.0.0'),
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),
    'send_default_pii' => false,
    'enable_logs' => true,
    'enable_metrics' => true,
    'traces_sample_rate' => 0.0,
    'profiles_sample_rate' => 0.0,
    'before_send' => [TelemetryPrivacy::class, 'event'],
    'before_send_log' => [TelemetryPrivacy::class, 'log'],
    'before_send_metric' => [TelemetryPrivacy::class, 'metric'],
    'data_collection' => [
        'user_info' => false,
        'cookies' => ['mode' => 'off'],
        'http_headers' => ['request' => ['mode' => 'off'], 'response' => ['mode' => 'off']],
        'http_bodies' => [],
        'url_query_params' => ['mode' => 'off'],
        'stack_frame_variables' => false,
        'frame_context_lines' => 0,
        'database_query_data' => false,
    ],
    'breadcrumbs' => ['logs' => false, 'cache' => false, 'http_client_requests' => false, 'sql_queries' => false, 'queue_info' => false, 'command_info' => false, 'notifications' => false, 'livewire' => false],
    'tracing' => ['http_client_requests' => false, 'sql_queries' => false, 'cache' => false, 'views' => false, 'queue_jobs' => false],
];
