<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'source_id',
        'crawl_job_id',
        'url',
        'url_hash',
        'title',
        'author',
        'published_at',
        'published_at_raw',
        'event_date',
        'event_date_confidence',
        'category_primary',
        'category_secondary',
        'category_confidence',
        'is_addon',
        'period_target',
        'content_hash',
        'content_length',
        'language',
        'summary',
        'sentiment',
        'quality_flags',
        'needs_review',
        'duplicate_of',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'event_date' => 'date',
        'category_secondary' => 'array',
        'quality_flags' => 'array',
        'is_addon' => 'boolean',
        'needs_review' => 'boolean',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function crawlJob(): BelongsTo
    {
        return $this->belongsTo(CrawlJob::class);
    }

    public function content(): HasOne
    {
        return $this->hasOne(ArticleContent::class);
    }

    public function entities(): HasMany
    {
        return $this->hasMany(ArticleEntity::class);
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'duplicate_of');
    }
}
