<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Crawl utama: tanggal 1 setiap bulan pukul 02:00 WIB (proses periode bulan sebelumnya)
        $schedule->command('crawl:monthly --window=main --trigger=cron')
            ->monthlyOn(1, '02:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Monthly crawl job FAILED');
            });

        // Crawl tambahan: tanggal 10 setiap bulan pukul 02:00 WIB (berita 1-10 yang membahas kejadian bulan sebelumnya)
        $schedule->command('crawl:monthly --window=addon --trigger=cron')
            ->monthlyOn(10, '02:00')
            ->timezone('Asia/Jakarta')
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('Monthly addon crawl job FAILED');
            });
    })
    ->create();
