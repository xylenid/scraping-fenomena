<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Normalisasi URL: canonicalization, hashing, dan validasi domain.
 */
class UrlNormalizer
{
    /**
     * Canonicalize URL: hapus tracking params, fragment, dan normalisasi host.
     */
    public static function canonicalize(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $url = trim($url);

        // Resolve relative URL terhadap base (jika ada)
        if (! preg_match('#^https?://#i', $url)) {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false || ! isset($parts['host'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? 'http');
        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '/';
        $query = $parts['query'] ?? null;

        // Hapus tracking params yang umum
        $trackingParams = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'fbclid', 'gclid', 'igshid', 'mc_cid', 'mc_eid', 'ref', 'source', 'from'];
        if ($query !== null) {
            parse_str($query, $params);
            foreach ($trackingParams as $tp) {
                unset($params[$tp]);
            }
            $query = http_build_query($params);
            $query = $query === '' ? null : $query;
        }

        $canonical = $scheme . '://' . $host . $path;
        if ($query !== null) {
            $canonical .= '?' . $query;
        }

        return $canonical;
    }

    public static function hash(string $url): string
    {
        return hash('sha256', $url);
    }

    public static function isSameDomain(string $url, string $domain): bool
    {
        $host = parse_url($url, PHP_URL_HOST);
        if ($host === null) {
            return false;
        }

        $host = strtolower($host);
        $domain = strtolower($domain);

        return $host === $domain || Str::endsWith($host, '.' . $domain);
    }
}