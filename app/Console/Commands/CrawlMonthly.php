<?php

namespace App\Console\Commands;

use App\Services\CrawlOrchestrator;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CrawlMonthly extends Command
{
    protected $signature = 'crawl:monthly
        {--period= : Periode analisis YYYY-MM (default: bulan sebelumnya)}
        {--window=main : main|addon}
        {--trigger=cron : cron|manual}';

    protected $description = 'Jalankan crawling berita bulanan untuk periode analisis tertentu';

    public function handle(CrawlOrchestrator $orchestrator): int
    {
        $period = $this->option('period');
        if (blank($period)) {
            $period = Carbon::now()->subMonth()->format('Y-m');
        }

        $window = $this->option('window');
        $trigger = $this->option('trigger');

        $this->info("Memulai crawl periode {$period} (window: {$window})...");

        $job = $orchestrator->run($period, $window, $trigger);

        $this->info("Job selesai: {$job->status}");
        $this->info('Stats: ' . json_encode($job->stats));

        return self::SUCCESS;
    }
}