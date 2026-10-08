<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleEntity extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'entity_type',
        'entity_name',
        'confidence',
    ];

    protected $casts = [
        'confidence' => 'float',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
