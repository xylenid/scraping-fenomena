<?php

use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\CrawlJobController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InternalScheduleController;
use App\Http\Controllers\Api\SourceController;
use Illuminate\Support\Facades\Route;

// Trigger scheduler eksternal (GitHub Actions / cron-job.org) — dilindungi CRON_TOKEN
Route::post('internal/run-schedule', [InternalScheduleController::class, 'runSchedule']);

Route::prefix('v1')->group(function () {
    // Dashboard stats
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);

    // Sumber berita
    Route::get('sources', [SourceController::class, 'index']);
    Route::get('sources/{source}', [SourceController::class, 'show']);

    // Artikel
    Route::get('articles', [ArticleController::class, 'index']);
    Route::get('articles/{article}', [ArticleController::class, 'show']);

    // Crawl jobs
    Route::get('crawl-jobs', [CrawlJobController::class, 'index']);
    Route::get('crawl-jobs/{job}', [CrawlJobController::class, 'show']);
    Route::post('crawl-jobs/run', [CrawlJobController::class, 'run']);
});