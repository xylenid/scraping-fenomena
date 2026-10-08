<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleContent extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'content_raw',
        'content_cleaned',
        'content_html',
        'extracted_by',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
