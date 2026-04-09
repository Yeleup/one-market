<?php

use App\Http\Middleware\EnsureClientIsAuthenticated;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HandleStorefrontInertiaRequests;
use App\Http\Middleware\RedirectIfClientAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'client.auth' => EnsureClientIsAuthenticated::class,
            'client.guest' => RedirectIfClientAuthenticated::class,
            'storefront.inertia' => HandleStorefrontInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
