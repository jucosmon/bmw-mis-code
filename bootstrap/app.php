<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'active' => \App\Http\Middleware\CheckIfUserIsActive::class,
            'notRestricted' => \App\Http\Middleware\CheckUserRestriction::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            // Stranded Incident routes
            '/stranded-incident/*',

            // Comments routes
            '/stranded-incident/comments/*',

            // Stranded Species routes
            '/stranded-incident/stranded-species/*',

            // Sighting routes
            '/sighting/*',

            // Guideline routes
            '/guideline/*',

            // Species management
            '/bpemo-admin/manage-species/*',

            // Account management routes
            '/bpemo-admin/manage-account/*',
            '/lgu-responder/manage-account/*',

            // Notifications
            '/notifications/*',

            // Profile and password routes
            '/profile/*',
            '/validate-password'
        ]);

        //
    })
    ->withSchedule(function ($schedule) {
        $schedule->command('app:unrestrict-users')->daily();
        $schedule->command('app:check-inactive-incidents')->everyMinute();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
