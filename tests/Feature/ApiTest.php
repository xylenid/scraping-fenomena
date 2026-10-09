<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\CrawlJob;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private Source $bisnis;

    private Source $kompas;

    private CrawlJob $job;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bisnis = Source::create([
            'code' => 'bisnis',
            'name' => 'Bisnis Indonesia',
            'domain' => 'bisnis.com',
            'rss_feeds' => [['url' => 'https://feeds.feedburner.com/Bisniscom', 'category' => 'news']],
            'enabled' => true,
        ]);

        $this->kompas = Source::create([
            'code' => 'kompas',
            'name' => 'Kompas',
            'domain' => 'kompas.com',
            'rss_feeds' => [],
            'enabled' => true,
        ]);

        $this->job = CrawlJob::create([
            'period' => '2026-09',
            'window_type' => 'main',
            'triggered_by' => 'cron',
            'status' => 'completed',
            'stats' => ['fetched' => 10, 'kept' => 8, 'failed' => 1],
        ]);

        $this->makeArticle($this->bisnis, 'commodity', 'Harga CPO naik', '2026-09-05');
        $this->makeArticle($this->bisnis, 'policy', 'Aturan ekspor baru', '2026-09-08', needsReview: true);
        $this->makeArticle($this->kompas, 'logistics', 'Pelabuhan padat', '2026-09-12', isAddon: true);
    }

    public function test_dashboard_stats_zero_fills_all_categories_and_lists_periods(): void
    {
        $response = $this->getJson('/api/v1/dashboard/stats');

        $response->assertOk()
            ->assertJsonPath('total_articles', 3)
            ->assertJsonPath('by_category.commodity', 1)
            ->assertJsonPath('by_category.policy', 1)
            ->assertJsonPath('by_category.logistics', 1)
            ->assertJsonPath('by_category.other', 0)
            ->assertJsonPath('by_category.unclear', 0)
            ->assertJsonPath('periods', ['2026-09'])
            ->assertJsonPath('latest_job.window_type', 'main');

        $this->assertSame(
            ['commodity', 'policy', 'logistics', 'other', 'unclear'],
            array_keys($response->json('by_category')),
        );
    }

    public function test_dashboard_stats_filters_by_period(): void
    {
        $this->makeArticle($this->bisnis, 'commodity', 'Berita bulan lain', '2026-10-02', period: '2026-10');

        $this->getJson('/api/v1/dashboard/stats?period=2026-10')
            ->assertOk()
            ->assertJsonPath('total_articles', 1)
            ->assertJsonPath('by_category.commodity', 1)
            ->assertJsonPath('by_category.policy', 0);
    }

    public function test_articles_can_be_filtered_by_source_code(): void
    {
        $this->getJson('/api/v1/articles?source=bisnis')
            ->assertOk()
            ->assertJsonPath('total', 2);

        $this->getJson('/api/v1/articles?source=kompas')
            ->assertOk()
            ->assertJsonPath('total', 1);
    }

    public function test_articles_reject_unknown_source_and_category(): void
    {
        $this->getJson('/api/v1/articles?source=does-not-exist')
            ->assertStatus(422)
            ->assertJsonValidationErrors('source');

        $this->getJson('/api/v1/articles?category=nope')
            ->assertStatus(422)
            ->assertJsonValidationErrors('category');

        $this->getJson('/api/v1/articles?per_page=500')
            ->assertStatus(422)
            ->assertJsonValidationErrors('per_page');
    }

    public function test_articles_support_category_review_and_addon_filters(): void
    {
        $this->getJson('/api/v1/articles?category=commodity')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/v1/articles?needs_review=1')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/v1/articles?is_addon=1')->assertOk()->assertJsonPath('total', 1);
    }

    public function test_sources_index_exposes_rss_feeds(): void
    {
        $response = $this->getJson('/api/v1/sources')->assertOk();

        $bisnis = collect($response->json())->firstWhere('code', 'bisnis');

        $this->assertSame('https://feeds.feedburner.com/Bisniscom', $bisnis['rss_feeds'][0]['url']);
        $this->assertSame(2, $bisnis['articles_count']);
    }

    public function test_crawl_job_periods_route_is_not_shadowed_by_show_route(): void
    {
        CrawlJob::create([
            'period' => '2026-10',
            'window_type' => 'addon',
            'triggered_by' => 'cron',
            'status' => 'queued',
        ]);

        $this->getJson('/api/v1/crawl-jobs/periods')
            ->assertOk()
            ->assertExactJson(['2026-10', '2026-09']);
    }

    public function test_crawl_job_show_includes_source_logs_and_failures(): void
    {
        $this->job->sourceLogs()->create([
            'source_id' => $this->bisnis->id,
            'status' => 'success',
            'fetched' => 5,
            'kept' => 5,
        ]);

        $this->job->failures()->create([
            'source_id' => $this->kompas->id,
            'url' => 'https://kompas.com/a',
            'error_type' => 'timeout',
            'error_message' => 'Request timed out',
        ]);

        $this->getJson("/api/v1/crawl-jobs/{$this->job->id}")
            ->assertOk()
            ->assertJsonPath('source_logs.0.source.code', 'bisnis')
            ->assertJsonPath('source_logs.0.status', 'success')
            ->assertJsonPath('failures.0.error_type', 'timeout');
    }

    private function makeArticle(
        Source $source,
        string $category,
        string $title,
        string $publishedAt,
        bool $needsReview = false,
        bool $isAddon = false,
        string $period = '2026-09',
    ): Article {
        return Article::create([
            'source_id' => $source->id,
            'crawl_job_id' => $this->job->id,
            'url' => 'https://example.test/'.md5($title),
            'url_hash' => md5($title),
            'title' => $title,
            'author' => 'Redaksi',
            'published_at' => $publishedAt,
            'category_primary' => $category,
            'is_addon' => $isAddon,
            'needs_review' => $needsReview,
            'period_target' => $period,
            'content_length' => 1200,
        ]);
    }
}
