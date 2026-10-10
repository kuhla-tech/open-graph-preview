<?php

use App\Http\Middleware\LoadInstanceSettings;
use Laravel\Fortify\Features;

return [
    'guard' => 'web', 'middleware' => ['web', LoadInstanceSettings::class, 'auth.session'], 'auth_middleware' => 'auth',
    'username' => 'email', 'email' => 'email', 'lowercase_usernames' => true,
    'home' => '/admin', 'prefix' => 'admin', 'domain' => null, 'views' => true,
    'limiters' => ['login' => 'login', 'two-factor' => 'two-factor', 'passkeys' => 'passkeys'],
    'features' => [Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]), Features::passkeys(['confirmPassword' => true])],
    'passkeys' => ['relying_party_id' => parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST), 'allowed_origins' => [rtrim(env('APP_URL', 'http://localhost'), '/')], 'timeout' => 60000],
];
