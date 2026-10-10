<?php

use App\Http\Middleware\LoadInstanceSettings;
use App\Http\Middleware\ObserveRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;
use Webauthn\Exception\AuthenticatorResponseVerificationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/admin/login');
        $middleware->redirectUsersTo('/admin');
        $middleware->web(append: [LoadInstanceSettings::class, ObserveRequests::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);
        $exceptions->dontReport([AuthenticatorResponseVerificationException::class]);
        $exceptions->render(function (AuthenticatorResponseVerificationException $exception, Request $request) {
            if ($request->is('admin/*')) {
                return response()->json(['message' => __('Die Passkey-Anfrage ist ungültig oder abgelaufen.'), 'errors' => ['credential' => [__('Die Passkey-Anfrage ist ungültig oder abgelaufen.')]]], 422);
            }
        });
        $exceptions->dontFlash(['url', 'sentry_dsn', 'current_password', 'password', 'password_confirmation']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
