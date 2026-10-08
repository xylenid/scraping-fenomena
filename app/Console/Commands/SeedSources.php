<?php

namespace App\Console\Commands;

use App\Models\Source;
use Illuminate\Console\Command;

class SeedSources extends Command
{
    protected $signature = 'sources:seed';

    protected $description = 'Isi konfigurasi sumber berita awal (Bisnis Indonesia, CNBC Indonesia, Detik, Kompas)';

    public function handle(): int
    {
        $sources = [
            [
                'code' => 'bisnis',
                'name' => 'Bisnis Indonesia',
                'domain' => 'bisnis.com',
                'base_url' => 'https://bisnis.com',
                'rss_feeds' => [
                    ['url' => 'https://feeds.feedburner.com/Bisniscom', 'category' => 'news'],
                ],
                'html_selectors' => [
                    'title' => 'h1',
                    'author' => '.author-name, .penulis',
                    'date' => '.date, time',
                    'content' => '.content-body, .article-content, .detail-content',
                ],
                'playwright_enabled' => true,
                'rate_limit_per_min' => 30,
                'enabled' => true,
            ],
            [
                'code' => 'cnbc',
                'name' => 'CNBC Indonesia',
                'domain' => 'cnbcindonesia.com',
                'base_url' => 'https://www.cnbcindonesia.com',
                'rss_feeds' => [
                    ['url' => 'https://www.cnbcindonesia.com/rss', 'category' => 'news'],
                    ['url' => 'https://www.cnbcindonesia.com/rss/market', 'category' => 'market'],
                    ['url' => 'https://www.cnbcindonesia.com/rss/ekonomi', 'category' => 'ekonomi'],
                ],
                'html_selectors' => [
                    'title' => 'h1',
                    'author' => '.author, .penulis',
                    'date' => '.date, time',
                    'content' => '.content, .article-content, .detail-text',
                ],
                'playwright_enabled' => true,
                'rate_limit_per_min' => 30,
                'enabled' => true,
            ],
            [
                'code' => 'detik',
                'name' => 'Detik',
                'domain' => 'detik.com',
                'base_url' => 'https://www.detik.com',
                'rss_feeds' => [
                    ['url' => 'https://news.detik.com/rss/', 'category' => 'news'],
                    ['url' => 'https://finance.detik.com/rss/', 'category' => 'finance'],
                ],
                'html_selectors' => [
                    'title' => 'h1.detail__title',
                    'author' => '.detail__author',
                    'date' => '.detail__date',
                    'content' => '.detail__body-text',
                ],
                'playwright_enabled' => true,
                'rate_limit_per_min' => 30,
                'enabled' => true,
            ],
            [
                'code' => 'kompas',
                'name' => 'Kompas',
                'domain' => 'kompas.com',
                'base_url' => 'https://www.kompas.com',
                'rss_feeds' => [
                    ['url' => 'https://indeks.kompas.com/rss/ekonomi', 'category' => 'ekonomi'],
                ],
                'html_selectors' => [
                    'title' => 'h1',
                    'author' => '.author, .penulis',
                    'date' => '.date, time',
                    'content' => '.read__content, .article-content',
                ],
                'playwright_enabled' => true,
                'rate_limit_per_min' => 30,
                'enabled' => true,
            ],
        ];

        foreach ($sources as $data) {
            Source::updateOrCreate(['code' => $data['code']], $data);
            $this->info("Source '{$data['code']}' siap.");
        }

        $this->info('Selesai. Total sumber: ' . Source::count());

        return self::SUCCESS;
    }
}