<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Support\StaleRouteCacheGuard;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

StaleRouteCacheGuard::invalidateIfStale(dirname(__DIR__));

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        __DIR__.'/../app/Console/Commands',
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        if (! config('database_backup.schedule_enabled', true)) {
            return;
        }

        $at = (string) config('database_backup.daily_at', '03:15');

        $schedule->command('app:backup-database')
            ->dailyAt($at)
            ->withoutOverlapping(45);
    })
    ->create();
