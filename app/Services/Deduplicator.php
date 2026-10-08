<?php

namespace App\Services;

use App\Models\Article;

/**
 * Deduplikasi artikel berdasarkan URL hash dan content hash.
 */
class Deduplicator
{
    /**
     * Cek apakah URL sudah pernah disimpan.
     */
    public static function urlExists(string $urlHash): bool
    {
        return Article::where('url_hash', $urlHash)->exists();
    }

    /**
     * Cari artikel dengan content hash yang sama (kemungkinan duplikat lintas sumber).
     *
     * @return Article|null
     */
    public static function findContentDuplicate(string $contentHash, ?int $excludeArticleId = null): ?Article
    {
        $query = Article::where('content_hash', $contentHash);
        if ($excludeArticleId !== null) {
            $query->where('id', '!=', $excludeArticleId);
        }

        return $query->first();
    }
}