<?php
use App\Http\Middleware\EnsureInstituteOperator;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
return Application::configure(basePath: dirname(__DIR__))
 ->withRouting(web: __DIR__.'/../routes/web.php',commands: __DIR__.'/../routes/console.php',health: '/up')
 ->withMiddleware(function (Middleware $middleware): void {$middleware->alias(['institute.operator'=>EnsureInstituteOperator::class]);})
 ->withSchedule(function (Schedule $schedule): void {$schedule->command('institutes:monitor-changes')->hourly()->withoutOverlapping(55);})
 ->withExceptions(function (Exceptions $exceptions): void {})
 ->create();