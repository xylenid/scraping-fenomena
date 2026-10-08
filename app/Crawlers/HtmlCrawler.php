<?php

namespace App\Crawlers;

use App\Models\Source;
use App\Services\ContentCleaner;
use App\Services\DateNormalizer;
use App\Services\UrlNormalizer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler as DomCrawler;

/**
 * Crawler berbasis HTML scraping dengan selector per sumber.
 */
class HtmlCrawler
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8',
            ],
            'http_errors' => false,
        ]);
    }

    /**
     * Ambil satu halaman artikel dan ekstrak kontennya.
     *
     * @return array{url: string, title: string, author: ?string, published_at: ?string, content: string, extracted_by: string}|null
     */
    public function fetchArticle(Source $source, string $url): ?array
    {
        $canonical = UrlNormalizer::canonicalize($url);
        if ($canonical === null) {
            return null;
        }

        try {
            $response = $this->client->get($canonical);
            if ($response->getStatusCode() !== 200) {
                Log::warning("HTML fetch failed [{$source->code}] {$canonical}: HTTP {$response->getStatusCode()}");
                return null;
            }

            $html = (string) $response->getBody();
            $crawler = new DomCrawler($html);
            $selectors = $source->html_selectors ?? [];

            $title = $this->extractText($crawler, $selectors['title'] ?? null) ?? $this->extractTitleTag($crawler);
            $author = $this->extractText($crawler, $selectors['author'] ?? null);
            $dateRaw = $this->extractText($crawler, $selectors['date'] ?? null);
            $contentHtml = $this->extractHtml($crawler, $selectors['content'] ?? null);

            if (blank($title) || blank($contentHtml)) {
                Log::warning("HTML parse incomplete [{$source->code}] {$canonical}: title=" . (blank($title) ? 'empty' : 'ok') . ' content=' . (blank($contentHtml) ? 'empty' : 'ok'));
                return null;
            }

            $cleaned = ContentCleaner::clean($contentHtml);
            if (strlen($cleaned) < 100) {
                Log::warning("HTML content too short [{$source->code}] {$canonical}: " . strlen($cleaned) . ' chars');
                return null;
            }

            return [
                'url' => $canonical,
                'title' => trim($title),
                'author' => $author !== null ? trim($author) : null,
                'published_at' => $dateRaw,
                'content' => $cleaned,
                'extracted_by' => 'domcrawler',
            ];
        } catch (GuzzleException $e) {
            Log::warning("HTML fetch error [{$source->code}] {$canonical}: {$e->getMessage()}");
            return null;
        } catch (\Throwable $e) {
            Log::warning("HTML parse error [{$source->code}] {$canonical}: {$e->getMessage()}");
            return null;
        }
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
            // Hapus suffix umum " - Nama Media"
            return preg_replace('/\s*[-|–]\s*[^-|–]+$/', '', $title) ?? $title;
        } catch (\Throwable) {
            return null;
        }
    }
}