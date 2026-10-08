<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

/**
 * Normalisasi tanggal publikasi dari berbagai format (Indonesia & ISO).
 */
class DateNormalizer
{
    private const MONTHS_ID = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
        'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
    ];

    /**
     * Parse string tanggal menjadi Carbon (UTC) atau null jika gagal.
     */
    public static function parse(?string $raw): ?Carbon
    {
        if (blank($raw)) {
            return null;
        }

        $raw = trim($raw);

        // 1. Coba format ISO / standar PHP
        try {
            $carbon = Carbon::parse($raw);
            if ($carbon->year > 1990 && $carbon->year < 2100) {
                return $carbon->utc();
            }
        } catch (InvalidFormatException) {
            // lanjut ke format Indonesia
        } catch (\Throwable) {
            // lanjut
        }

        // 2. Format Indonesia: "8 Oktober 2026, 14:30 WIB" / "8 Okt 2026"
        $normalized = str_replace(['WIB', 'WITA', 'WIT'], '', $raw);
        $normalized = preg_replace('/\s+/', ' ', trim($normalized));

        // Bulan penuh: "8 Oktober 2026"
        if (preg_match('/(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})/', $normalized, $m)) {
            $month = self::monthToNumber($m[2]);
            if ($month !== null) {
                return Carbon::createFromFormat('Y-m-d H:i:s', sprintf('%04d-%02d-%02d 00:00:00', $m[3], $month, $m[1]), 'Asia/Jakarta')->utc();
            }
        }

        // Bulan singkat: "8 Okt 2026"
        if (preg_match('/(\d{1,2})\s+([A-Za-z]{3})\.?\s+(\d{4})/', $normalized, $m)) {
            $month = self::monthToNumber($m[2]);
            if ($month !== null) {
                return Carbon::createFromFormat('Y-m-d H:i:s', sprintf('%04d-%02d-%02d 00:00:00', $m[3], $month, $m[1]), 'Asia/Jakarta')->utc();
            }
        }

        // "2026-10-08" tanpa waktu
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            return Carbon::createFromFormat('Y-m-d H:i:s', sprintf('%04d-%02d-%02d 00:00:00', $m[1], $m[2], $m[3]), 'Asia/Jakarta')->utc();
        }

        return null;
    }

    private static function monthToNumber(string $month): ?int
    {
        $month = strtolower(trim($month, '. '));
        if (isset(self::MONTHS_ID[$month])) {
            return self::MONTHS_ID[$month];
        }

        // Nama bulan Inggris
        $en = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
        ];
        $key = substr($month, 0, 3);
        if (isset($en[$key])) {
            return $en[$key];
        }

        return null;
    }
}