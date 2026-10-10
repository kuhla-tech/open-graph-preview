<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('opengraph:check-production', function () {
    $missing = [];
    foreach (['legal.hosting_location', 'legal.web_logs_days', 'legal.matomo_days', 'legal.sentry_days'] as $key) {
        if (! config($key)) {
            $missing[] = $key;
        }
    }
    if (config('monitoring.allow_private_targets')) {
        $missing[] = 'PREVIEW_ALLOW_PRIVATE_TARGETS=false';
    }
    if (config('app.debug')) {
        $missing[] = 'APP_DEBUG=false';
    }
    if ($missing) {
        $this->error('Production configuration incomplete: '.implode(', ', $missing));

        return 1;
    }
    $this->info('Production configuration checks passed. Verify Matomo IP anonymization, real retention settings and webserver log policy separately.');

    return 0;
})->purpose('Check hosted configuration and legal retention fields');
