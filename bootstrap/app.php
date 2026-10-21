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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\UpdateLastSeen::class,
        ]);
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermissions::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'webhooks/email/inbound',
            'upload-chunk',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // $schedule->command('tasks:process-recurring')->daily();
        // $schedule->command('tasks:process-sla')->hourly();
        $schedule->command('tickets:fetch')->everyMinute();
        // $schedule->command('tasks:summarize-ai')->dailyAt('18:00');
    })->create();
