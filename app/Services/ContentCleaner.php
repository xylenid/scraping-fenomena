<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Pembersihan konten: strip boilerplate, normalisasi whitespace, hitung hash.
 */
class ContentCleaner
{
    /**
     * Bersihkan teks artikel: hapus boilerplate umum, normalisasi spasi.
     */
    public static function clean(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        // Hapus tag script/style
        $text = preg_replace('#<(script|style|noscript)[^>]*>.*?</\1>#is', ' ', $html) ?? $html;

        // Hapus tag HTML, pertahankan paragraf
        $text = preg_replace('#<br\s*/?>#i', "\n", $text) ?? $text;
        $text = preg_replace('#</p>#i', "\n", $text) ?? $text;
        $text = strip_tags($text);

        // Decode entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Hapus boilerplate umum
        $boilerplate = [
            'Baca juga:', 'Baca Juga:', 'Simak juga:', 'Simak Juga:',
            'ADVERTISEMENT', 'ADVERTISEMENT', 'IKLAN', 'iklan',
            'Copyright', 'Hak cipta', 'All rights reserved',
            'Bagikan artikel ini', 'Share', 'Tweet', 'WhatsApp',
            'Dapatkan update', 'Berlangganan', 'Newsletter',
            'Komentar', 'Lihat komentar', 'Tulis komentar',
            'Follow kami', 'Ikuti kami', 'Follow our',
        ];
        foreach ($boilerplate as $bp) {
            $text = str_replace($bp, ' ', $text);
        }

        // Normalisasi whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = trim($text);

        return $text;
    }

    public static function hash(string $cleanedText): string
    {
        // Normalisasi untuk hash: lowercase, hapus whitespace berlebih
        $normalized = Str::lower($cleanedText);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return hash('sha256', trim($normalized));
    }

    /**
     * Ambil lead (paragraf pertama) untuk event-date extraction.
     */
    public static function lead(string $cleanedText, int $maxChars = 500): string
    {
        $lead = Str::limit($cleanedText, $maxChars, '');
        return trim($lead);
    }
}