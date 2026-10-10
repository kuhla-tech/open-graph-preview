<?php

return [
    'controller_name' => '', 'brand' => '', 'address' => '', 'postcode_city' => '',
    'country' => '', 'email' => '', 'phone' => '', 'extra' => '',
    'hosting_location' => env('LEGAL_HOSTING_LOCATION', ''),
    'web_logs_days' => env('LEGAL_WEB_LOGS_DAYS', 7),
    'matomo_days' => env('LEGAL_MATOMO_DAYS', 180),
    'sentry_days' => env('LEGAL_SENTRY_DAYS', 30),
];
