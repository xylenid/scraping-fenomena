<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrawlJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'period',
        'window_type',
        'triggered_by',
        'status',
        'started_at',
        'finished_at',
        'stats',
        'notes',
    ];

    protected $casts = [
        'stats' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function sourceLogs(): HasMany
    {
        return $this->hasMany(CrawlSourceLog::class);
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    public function failures(): HasMany
    {
        return $this->hasMany(CrawlFailure::class);
    }
}
