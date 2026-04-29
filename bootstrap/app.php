<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin'       => \App\Http\Middleware\EnsureAdmin::class,
            'guest.admin' => \App\Http\Middleware\RedirectIfAdmin::class,
            'loadtest'    => \App\Http\Middleware\EnsureLoadTestSecret::class,
        ]);

        // Loadtest endpoints tidak perlu CSRF (dipanggil oleh k6, bukan browser)
        $middleware->validateCsrfTokens(except: [
            'loadtest/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
