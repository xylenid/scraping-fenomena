<?php

namespace App\Crawlers;

use App\Models\Source;
use App\Services\ContentCleaner;
use App\Services\DateNormalizer;
use App\Services\UrlNormalizer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Laminas\Feed\Reader\Reader;

/**
 * Crawler berbasis RSS/Atom feed.
 */
class RssCrawler
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; TradeNewsCrawler/1.0; +internal-research)',
                'Accept' => 'application/rss+xml, application/atom+xml, application/xml, text/xml, */*',
            ],
            'http_errors' => false,
        ]);
    }

    /**
     * Ambil artikel dari semua RSS feed milik sumber.
     *
     * @return array<int, array{url: string, title: string, author: ?string, published_at: ?string, content: string, extracted_by: string}>
     */
    public function fetch(Source $source): array
    {
        $articles = [];
        $feeds = $source->rss_feeds ?? [];

        foreach ($feeds as $feed) {
            $feedUrl = is_array($feed) ? ($feed['url'] ?? null) : $feed;
            if (blank($feedUrl)) {
                continue;
            }

            try {
                $response = $this->client->get($feedUrl);
                if ($response->getStatusCode() !== 200) {
                    Log::warning("RSS fetch failed [{$source->code}] {$feedUrl}: HTTP {$response->getStatusCode()}");
                    continue;
                }

                $body = (string) $response->getBody();
                $feedData = Reader::importString($body);

                foreach ($feedData as $entry) {
                    $url = UrlNormalizer::canonicalize($entry->getLink());
                    if ($url === null) {
                        continue;
                    }

                    $title = trim($entry->getTitle() ?? '');
                    if ($title === '') {
                        continue;
                    }

                    $publishedRaw = null;
                    try {
                        $date = $entry->getDateModified() ?? $entry->getDateCreated();
                        if ($date !== null) {
                            $publishedRaw = $date->format('Y-m-d H:i:s');
                        }
                    } catch (\Throwable) {
                        // tanggal tidak tersedia
                    }

                    $content = '';
                    try {
                        $content = $entry->getContent() ?? $entry->getDescription() ?? '';
                    } catch (\Throwable) {
                        $content = $entry->getDescription() ?? '';
                    }

                    $articles[] = [
                        'url' => $url,
                        'title' => $title,
                        'author' => $this->extractAuthor($entry),
                        'published_at' => $publishedRaw,
                        'content' => ContentCleaner::clean($content),
                        'extracted_by' => 'rss',
                    ];
                }
            } catch (GuzzleException $e) {
                Log::warning("RSS fetch error [{$source->code}] {$feedUrl}: {$e->getMessage()}");
            } catch (\Throwable $e) {
                Log::warning("RSS parse error [{$source->code}] {$feedUrl}: {$e->getMessage()}");
            }
        }

        return $articles;
    }

    private function extractAuthor($entry): ?string
    {
        try {
            $authors = $entry->getAuthors();
            if (! empty($authors)) {
                return $authors[0]['name'] ?? null;
            }
        } catch (\Throwable) {
            // ignore
        }

        return null;
    }
}