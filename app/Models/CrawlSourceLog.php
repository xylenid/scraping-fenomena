<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrawlSourceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'crawl_job_id',
        'source_id',
        'status',
        'fetched',
        'kept',
        'failed',
        'error_summary',
        'started_at',
        'finished_at',
        'last_url',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function crawlJob(): BelongsTo
    {
        return $this->belongsTo(CrawlJob::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
