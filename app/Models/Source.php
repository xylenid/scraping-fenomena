<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'domain',
        'base_url',
        'rss_feeds',
        'api_config',
        'html_selectors',
        'playwright_enabled',
        'robots_policy',
        'rate_limit_per_min',
        'enabled',
    ];

    protected $casts = [
        'rss_feeds' => 'array',
        'api_config' => 'array',
        'html_selectors' => 'array',
        'playwright_enabled' => 'boolean',
        'enabled' => 'boolean',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function crawlSourceLogs(): HasMany
    {
        return $this->hasMany(CrawlSourceLog::class);
    }
}
