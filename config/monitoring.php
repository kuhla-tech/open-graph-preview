<?php

return [
    'matomo_enabled' => env('MATOMO_ENABLED', env('APP_ENV') === 'production'),
    'matomo_url' => env('MATOMO_URL', ''),
    'matomo_site_id' => (int) env('MATOMO_SITE_ID', 12),
    'enabled' => env('MONITORING_ENABLED', env('APP_ENV') === 'production'),
    'allow_private_targets' => env('PREVIEW_ALLOW_PRIVATE_TARGETS', env('APP_ENV') !== 'production'),
];
