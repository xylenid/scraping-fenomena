<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Ekstraksi tanggal kejadian (event date) dari judul/lead artikel.
 * Digunakan untuk aturan "berita tambahan 1-10 Oktober harus membahas kejadian September".
 */
class EventDateExtractor
{
    private const MONTHS_ID = [
        'januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4,
        'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8,
        'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12,
    ];

    /**
     * @return array{date: ?string, confidence: string, matched: string|null}
     */
    public static function extract(string $title, ?string $lead = null): array
    {
        $text = $title . ' ' . ($lead ?? '');
        $text = Str::lower($text);

        // 1. Tanggal eksplisit: "8 September 2026" / "8 Sep 2026"
        if (preg_match('/(\d{1,2})\s+([a-z]+)\.?\s+(\d{4})/', $text, $m)) {
            $month = self::monthToNumber($m[2]);
            if ($month !== null) {
                return [
                    'date' => sprintf('%04d-%02d-%02d', $m[3], $month, $m[1]),
                    'confidence' => 'high',
                    'matched' => $m[0],
                ];
            }
        }

        // 2. ISO: "2026-09-08"
        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $text, $m)) {
            return [
                'date' => sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]),
                'confidence' => 'high',
                'matched' => $m[0],
            ];
        }

        // 3. Frasa temporal: "pada September 2026", "September lalu", "awal September"
        if (preg_match('/(?:pada|di|sejak|mulai|awal|akhir|pertengahan|bulan)\s+([a-z]+)\s+(\d{4})/', $text, $m)) {
            $month = self::monthToNumber($m[1]);
            if ($month !== null) {
                return [
                    'date' => sprintf('%04d-%02d-01', $m[2], $month),
                    'confidence' => 'med',
                    'matched' => $m[0],
                ];
            }
        }

        if (preg_match('/([a-z]+)\s+lalu/', $text, $m)) {
            $month = self::monthToNumber($m[1]);
            if ($month !== null) {
                $year = now()->year;
                if ($month > now()->month) {
                    $year--;
                }
                return [
                    'date' => sprintf('%04d-%02d-01', $year, $month),
                    'confidence' => 'med',
                    'matched' => $m[0],
                ];
            }
        }

        // 4. Bulan saja tanpa tahun (infer dari konteks)
        foreach (self::MONTHS_ID as $name => $num) {
            if (Str::contains($text, $name)) {
                return [
                    'date' => sprintf('%04d-%02d-01', now()->year, $num),
                    'confidence' => 'low',
                    'matched' => $name,
                ];
            }
        }

        return ['date' => null, 'confidence' => 'low', 'matched' => null];
    }

    private static function monthToNumber(string $month): ?int
    {
        $month = strtolower(trim($month, '. '));
        if (isset(self::MONTHS_ID[$month])) {
            return self::MONTHS_ID[$month];
        }

        $en = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
        ];
        $key = substr($month, 0, 3);
        return $en[$key] ?? null;
    }
}