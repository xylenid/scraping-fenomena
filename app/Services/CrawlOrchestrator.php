<?php

namespace App\Services;

use App\Crawlers\HtmlCrawler;
use App\Crawlers\PlaywrightCrawler;
use App\Crawlers\RssCrawler;
use App\Models\Article;
use App\Models\ArticleContent;
use App\Models\CrawlFailure;
use App\Models\CrawlJob;
use App\Models\CrawlSourceLog;
use App\Models\Source;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Orkestrator pipeline crawling bulanan:
 * fetch (RSS -> HTML -> Playwright) -> clean -> dedup -> filter periode/kejadian -> kategorisasi -> persist.
 */
class CrawlOrchestrator
{
    private RssCrawler $rssCrawler;
    private HtmlCrawler $htmlCrawler;
    private PlaywrightCrawler $playwrightCrawler;

    public function __construct()
    {
        $this->rssCrawler = new RssCrawler();
        $this->htmlCrawler = new HtmlCrawler();
        $this->playwrightCrawler = new PlaywrightCrawler();
    }

    /**
     * Jalankan crawl untuk satu periode.
     *
     * @param string $period 'YYYY-MM' (periode analisis, mis. 2026-09)
     * @param string $windowType 'main' | 'addon'
     * @param string $triggeredBy 'cron' | 'manual'
     */
    public function run(string $period, string $windowType = 'main', string $triggeredBy = 'manual'): CrawlJob
    {
        // Cegah double-run untuk kombinasi yang sama
        $existing = CrawlJob::where('period', $period)
            ->where('window_type', $windowType)
            ->whereIn('status', ['queued', 'running'])
            ->first();

        if ($existing) {
            Log::info("Crawl job already running for {$period} ({$windowType}), skipping.");
            return $existing;
        }

        $job = CrawlJob::create([
            'period' => $period,
            'window_type' => $windowType,
            'triggered_by' => $triggeredBy,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $sources = Source::where('enabled', true)->get();
        $stats = ['fetched' => 0, 'kept' => 0, 'skipped_duplicate' => 0, 'skipped_period' => 0, 'skipped_quality' => 0, 'failed' => 0];
        $sourceStatuses = [];

        foreach ($sources as $source) {
            $log = CrawlSourceLog::create([
                'crawl_job_id' => $job->id,
                'source_id' => $source->id,
                'status' => 'running',
                'started_at' => now(),
            ]);

            try {
                $result = $this->crawlSource($job, $source, $period, $windowType);
                $stats['fetched'] += $result['fetched'];
                $stats['kept'] += $result['kept'];
                $stats['skipped_duplicate'] += $result['skipped_duplicate'];
                $stats['skipped_period'] += $result['skipped_period'];
                $stats['skipped_quality'] += $result['skipped_quality'];
                $stats['failed'] += $result['failed'];

                $sourceStatuses[$source->code] = $result['status'];

                $log->update([
                    'status' => $result['status'],
                    'fetched' => $result['fetched'],
                    'kept' => $result['kept'],
                    'failed' => $result['failed'],
                    'error_summary' => $result['error_summary'],
                    'finished_at' => now(),
                ]);
            } catch (\Throwable $e) {
                Log::error("Crawl source failed [{$source->code}]: {$e->getMessage()}", ['trace' => $e->getTraceAsString()]);
                $sourceStatuses[$source->code] = 'failed';

                $log->update([
                    'status' => 'failed',
                    'error_summary' => $e->getMessage(),
                    'finished_at' => now(),
                ]);
            }
        }

        $jobStatus = in_array('failed', $sourceStatuses, true) ? 'partial' : 'completed';
        $job->update([
            'status' => $jobStatus,
            'finished_at' => now(),
            'stats' => $stats,
            'notes' => 'Sources: ' . implode(', ', array_map(fn ($s, $st) => "$s=$st", array_keys($sourceStatuses), $sourceStatuses)),
        ]);

        return $job;
    }

    /**
     * Crawl satu sumber: RSS -> HTML -> Playwright fallback.
     *
     * @return array{fetched: int, kept: int, skipped_duplicate: int, skipped_period: int, skipped_quality: int, failed: int, status: string, error_summary: ?string}
     */
    private function crawlSource(CrawlJob $job, Source $source, string $period, string $windowType): array
    {
        $result = [
            'fetched' => 0,
            'kept' => 0,
            'skipped_duplicate' => 0,
            'skipped_period' => 0,
            'skipped_quality' => 0,
            'failed' => 0,
            'status' => 'success',
            'error_summary' => null,
        ];

        $periodStart = Carbon::parse($period . '-01')->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $addonStart = $periodStart->copy()->addMonth()->startOfMonth();
        $addonEnd = $addonStart->copy()->addDays(9)->endOfDay();

        // 1. RSS
        $rssArticles = $this->rssCrawler->fetch($source);
        $result['fetched'] += count($rssArticles);

        foreach ($rssArticles as $article) {
            $this->processArticle($job, $source, $article, $period, $windowType, $periodStart, $periodEnd, $addonStart, $addonEnd, $result);
        }

        // 2. HTML fallback untuk artikel yang RSS tidak lengkap (opsional: hanya jika RSS kosong)
        if (count($rssArticles) === 0 && ! empty($source->html_selectors)) {
            $this->htmlFallback($job, $source, $period, $windowType, $periodStart, $periodEnd, $addonStart, $addonEnd, $result);
        }

        // 3. Playwright fallback
        if ($source->playwright_enabled && count($rssArticles) === 0) {
            $this->playwrightFallback($job, $source, $period, $windowType, $periodStart, $periodEnd, $addonStart, $addonEnd, $result);
        }

        if ($result['failed'] > 0 && $result['fetched'] === 0) {
            $result['status'] = 'failed';
            $result['error_summary'] = 'All fetches failed';
        } elseif ($result['failed'] > 0) {
            $result['status'] = 'partial';
        }

        return $result;
    }

    private function htmlFallback(CrawlJob $job, Source $source, string $period, string $windowType, Carbon $periodStart, Carbon $periodEnd, Carbon $addonStart, Carbon $addonEnd, array &$result): void
    {
        // Untuk MVP: fallback HTML memerlukan daftar URL (dari listing page).
        // Implementasi listing page crawler dapat ditambahkan per sumber.
        Log::info("HTML fallback for [{$source->code}] requires listing page crawler (not yet implemented for this source).");
    }

    private function playwrightFallback(CrawlJob $job, Source $source, string $period, string $windowType, Carbon $periodStart, Carbon $periodEnd, Carbon $addonStart, Carbon $addonEnd, array &$result): void
    {
        Log::info("Playwright fallback for [{$source->code}] requires listing page crawler (not yet implemented for this source).");
    }

    /**
     * Proses satu artikel: clean -> dedup -> filter -> kategorisasi -> persist.
     */
    private function processArticle(CrawlJob $job, Source $source, array $article, string $period, string $windowType, Carbon $periodStart, Carbon $periodEnd, Carbon $addonStart, Carbon $addonEnd, array &$result): void
    {
        $urlHash = UrlNormalizer::hash($article['url']);

        // Dedup URL
        if (Deduplicator::urlExists($urlHash)) {
            $result['skipped_duplicate']++;
            return;
        }

        $publishedAt = DateNormalizer::parse($article['published_at']);
        $contentHash = ContentCleaner::hash($article['content']);

        // Dedup konten (lintas sumber)
        $contentDup = Deduplicator::findContentDuplicate($contentHash);
        if ($contentDup !== null) {
            $result['skipped_duplicate']++;
            return;
        }

        // Filter periode
        $isAddon = $windowType === 'addon';
        $inMainWindow = $publishedAt !== null && $publishedAt->between($periodStart, $periodEnd);
        $inAddonWindow = $publishedAt !== null && $publishedAt->between($addonStart, $addonEnd);

        if ($windowType === 'main' && ! $inMainWindow) {
            $result['skipped_period']++;
            return;
        }

        if ($isAddon) {
            if (! $inAddonWindow) {
                $result['skipped_period']++;
                return;
            }

            // Aturan tambahan: berita 1-10 Oktober harus membahas kejadian September
            $event = EventDateExtractor::extract($article['title'], ContentCleaner::lead($article['content']));
            $eventInTarget = $event['date'] !== null && Carbon::parse($event['date'])->between($periodStart, $periodEnd);

            if (! $eventInTarget) {
                $result['skipped_period']++;
                return;
            }
        }

        // Kategorisasi
        $category = Categorizer::categorize($article['title'], $article['content']);

        // Quality flags
        $flags = [];
        if (strlen($article['content']) < 200) {
            $flags[] = 'short';
        }
        if ($publishedAt === null) {
            $flags[] = 'no_publish_date';
        }
        if ($category['confidence'] === 'low') {
            $flags[] = 'low_category_confidence';
        }

        $needsReview = $category['confidence'] === 'low' || in_array('no_publish_date', $flags, true);

        try {
            DB::transaction(function () use ($job, $source, $article, $publishedAt, $contentHash, $category, $flags, $needsReview, $period, $isAddon, $urlHash, &$result) {
                $articleModel = Article::create([
                    'source_id' => $source->id,
                    'crawl_job_id' => $job->id,
                    'url' => $article['url'],
                    'url_hash' => $urlHash,
                    'title' => $article['title'],
                    'author' => $article['author'] ?? null,
                    'published_at' => $publishedAt,
                    'published_at_raw' => $article['published_at'] ?? null,
                    'event_date' => null,
                    'event_date_confidence' => null,
                    'category_primary' => $category['primary'],
                    'category_secondary' => $category['secondary'],
                    'category_confidence' => $category['confidence'],
                    'is_addon' => $isAddon,
                    'period_target' => $period,
                    'content_hash' => $contentHash,
                    'content_length' => strlen($article['content']),
                    'language' => 'id',
                    'quality_flags' => $flags,
                    'needs_review' => $needsReview,
                ]);

                ArticleContent::create([
                    'article_id' => $articleModel->id,
                    'content_raw' => $article['content'],
                    'content_cleaned' => $article['content'],
                    'extracted_by' => $article['extracted_by'] ?? 'rss',
                ]);

                $result['kept']++;
            });
        } catch (\Throwable $e) {
            $result['failed']++;
            CrawlFailure::create([
                'crawl_job_id' => $job->id,
                'source_id' => $source->id,
                'url' => $article['url'],
                'error_type' => 'parse',
                'error_message' => $e->getMessage(),
                'occurred_at' => now(),
            ]);
            Log::error("Article persist failed [{$source->code}]: {$e->getMessage()}");
        }
    }
}