<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckBlockedIp;
use App\Http\Middleware\OAuthBearerMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->validateCsrfTokens(except: [
            'oauth/*',
            'oauth/token',
            'oauth/logout',
            'api/*',
        ]);

        $middleware->append(SecurityHeadersMiddleware::class);
        $middleware->append(CheckBlockedIp::class);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'role' => RoleMiddleware::class,
            'oauth.bearer' => OAuthBearerMiddleware::class,
            'check.blocked.ip' => CheckBlockedIp::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
