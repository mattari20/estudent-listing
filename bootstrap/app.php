<?php

use App\Http\Middleware\EnsureInstituteOperator;
use App\Http\Middleware\EnsureInstituteAdmin;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([AppServiceProvider::class])
    ->withCommands([__DIR__.'/../app/Console/Commands'])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'institutes/api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'institute.operator' => EnsureInstituteOperator::class,
            'institute.admin' => EnsureInstituteAdmin::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request): string => route('institutes.login'));
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('institutes:bootstrap-agent')
            ->hourly()
            ->withoutOverlapping(55);

        $schedule->command('institutes:monitor-changes')
            ->hourly()
            ->withoutOverlapping(55);
    })
    ->withExceptions(function (Exceptions $exceptions): void {})
    ->create();
