<?php

namespace App\Crawlers;

use App\Models\Source;
use App\Services\ContentCleaner;
use App\Services\UrlNormalizer;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

/**
 * Crawler berbasis browser (Playwright) untuk website yang membutuhkan JavaScript.
 * Memanggil Node.js script via CLI.
 */
class PlaywrightCrawler
{
    private string $scriptPath;

    public function __construct()
    {
        $this->scriptPath = base_path('scripts/playwright_fetch.js');
    }

    /**
     * Ambil halaman dengan rendering JavaScript.
     *
     * @return array{url: string, title: string, author: ?string, published_at: ?string, content: string, extracted_by: string}|null
     */
    public function fetchArticle(Source $source, string $url): ?array
    {
        $canonical = UrlNormalizer::canonicalize($url);
        if ($canonical === null) {
            return null;
        }

        if (! is_file($this->scriptPath)) {
            Log::warning('Playwright script not found: ' . $this->scriptPath);
            return null;
        }

        $cmd = sprintf(
            'node %s %s 2>&1',
            escapeshellarg($this->scriptPath),
            escapeshellarg($canonical)
        );

        $output = shell_exec($cmd);
        if ($output === null || $output === '') {
            Log::warning("Playwright fetch failed [{$source->code}] {$canonical}: empty output");
            return null;
        }

        $data = json_decode($output, true);
        if (! is_array($data) || empty($data['html'])) {
            Log::warning("Playwright fetch failed [{$source->code}] {$canonical}: invalid JSON output");
            return null;
        }

        $crawler = new DomCrawler($data['html']);
        $selectors = $source->html_selectors ?? [];

        $title = $this->extractText($crawler, $selectors['title'] ?? null) ?? $this->extractTitleTag($crawler);
        $author = $this->extractText($crawler, $selectors['author'] ?? null);
        $dateRaw = $this->extractText($crawler, $selectors['date'] ?? null);
        $contentHtml = $this->extractHtml($crawler, $selectors['content'] ?? null);

        if (blank($title) || blank($contentHtml)) {
            Log::warning("Playwright parse incomplete [{$source->code}] {$canonical}");
            return null;
        }

        $cleaned = ContentCleaner::clean($contentHtml);
        if (strlen($cleaned) < 100) {
            Log::warning("Playwright content too short [{$source->code}] {$canonical}");
            return null;
        }

        return [
            'url' => $canonical,
            'title' => trim($title),
            'author' => $author !== null ? trim($author) : null,
            'published_at' => $dateRaw,
            'content' => $cleaned,
            'extracted_by' => 'playwright',
        ];
    }

    private function extractText(DomCrawler $crawler, ?string $selector): ?string
    {
        if (blank($selector)) {
            return null;
        }

        try {
            $node = $crawler->filter($selector)->first();
            if ($node->count() === 0) {
                return null;
            }

            return trim($node->text());
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractHtml(DomCrawler $crawler, ?string $selector): ?string
    {
        if (blank($selector)) {
            return null;
        }

        try {
            $node = $crawler->filter($selector)->first();
            if ($node->count() === 0) {
                return null;
            }

            return $node->html();
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractTitleTag(DomCrawler $crawler): ?string
    {
        try {
            $node = $crawler->filter('title')->first();
            if ($node->count() === 0) {
                return null;
            }

            $title = trim($node->text());
            return preg_replace('/\s*[-|–]\s*[^-|–]+$/', '', $title) ?? $title;
        } catch (\Throwable) {
            return null;
        }
    }
}