<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        // Use custom CSRF token verification middleware
        $middleware->validateCsrfTokens(except: [
            'api/forgot-password',
            'api/reset-password',
        ]);

        // Enable CORS for all routes
        $middleware->web(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        // Enable sessions and CORS for API routes
        // Use custom StartSessionIfExists to prevent creating new sessions
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
            \App\Http\Middleware\StartSessionIfExists::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
