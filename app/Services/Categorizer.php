<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Kategorisasi fenomena: komoditas, kebijakan, logistik/rantai pasok.
 * Rule-based dengan lexicon + skor.
 */
class Categorizer
{
    private const CATEGORIES = ['commodity', 'policy', 'logistics'];

    private const LEXICONS = [
        'commodity' => [
            // Komoditas ekspor utama Indonesia
            'cpo', 'minyak sawit', 'sawit', 'batu bara', 'batubara', 'nikel', 'karet',
            'kopi', 'teh', 'kakao', 'cokelat', 'emas', 'tembaga', 'timah', 'aluminium',
            'baja', 'besi', 'pulp', 'kertas', 'udang', 'ikan', 'rumput laut', 'kelapa sawit',
            'crude palm oil', 'palm oil', 'coal', 'nickel', 'rubber', 'coffee', 'tin',
            'harga komoditas', 'harga sawit', 'harga batu bara', 'harga nikel', 'harga emas',
            'produksi sawit', 'produksi batu bara', 'produksi nikel', 'stok komoditas',
            'ekspor komoditas', 'volume ekspor', 'nilai ekspor', 'harga ekspor',
            'panen', 'gagal panen', 'cuaca ekstrem', 'kekeringan', 'banjir', 'harga minyak',
            'minyak mentah', 'brent', 'wti', 'gas alam', 'lng', 'komoditas',
        ],
        'policy' => [
            'tarif', 'bea masuk', 'bea keluar', 'pajak impor', 'pajak ekspor', 'pungutan ekspor',
            'perjanjian dagang', 'perdagangan bebas', 'fta', 'cepa', 'rcep', 'apec', 'wto',
            'larangan ekspor', 'larangan impor', 'pembatasan ekspor', 'pembatasan impor',
            'kuota', 'subsidi', 'regulasi', 'kebijakan', 'peraturan', 'permen', 'pp no',
            'undang-undang', 'uu ', 'deregulasi', 'insentif', 'hambatan perdagangan',
            'non-tarif', 'ntb', 'safeguard', 'anti-dumping', 'bea', 'cukai', 'pajak',
            'kementerian perdagangan', 'kemendag', 'kementerian keuangan', 'kemenkeu',
            'kementerian perindustrian', 'kemenperin', 'kementerian esdm', 'kementerian luar negeri',
            'pemerintah', 'presiden', 'menteri', 'kebijakan perdagangan', 'kebijakan impor',
            'kebijakan ekspor', 'perizinan', 'izin ekspor', 'izin impor', 'sertifikasi',
            'standar nasional', 'snI', 'halal', 'bea cukai', 'djbc', 'kppu', 'perdagangan internasional',
        ],
        'logistics' => [
            'pelabuhan', 'kapal', 'kontainer', 'peti kemas', 'freight', 'ongkos angkut',
            'ongkir', 'tarif angkut', 'jalur laut', 'selat hormuz', 'selat malaka', 'laut merah',
            'red sea', 'terusan suez', 'kanal suez', 'gangguan rantai pasok', 'rantai pasok',
            'supply chain', 'logistik', 'pengiriman', 'pengapalan', 'shipping', 'bongkar muat',
            'dwell time', 'congestion', 'kemacetan pelabuhan', 'krisis logistik', 'transshipment',
            'transhipment', 'harbor', 'port', 'vessel', 'cargo', 'muatan', 'angkutan laut',
            'angkutan udara', 'kargo udara', 'hujan', 'badai', 'topan', 'cuaca buruk',
            'gangguan cuaca', 'keterlambatan pengiriman', 'delay', 'backlog', 'kekurangan kontainer',
            'krisis kontainer', 'biaya logistik', 'biaya pengiriman', 'logistics',
        ],
    ];

    /**
     * @return array{primary: string, secondary: array, confidence: string, scores: array}
     */
    public static function categorize(string $title, string $content): array
    {
        $text = Str::lower($title . ' ' . Str::limit($content, 3000));
        $scores = [];

        foreach (self::LEXICONS as $category => $terms) {
            $score = 0;
            foreach ($terms as $term) {
                // Bobot lebih tinggi jika term ada di judul
                $inTitle = Str::contains(Str::lower($title), $term);
                $inContent = Str::contains($text, $term);
                if ($inTitle) {
                    $score += 2;
                } elseif ($inContent) {
                    $score += 1;
                }
            }
            $scores[$category] = $score;
        }

        arsort($scores);
        $primary = array_key_first($scores);
        $topScore = $scores[$primary];

        if ($topScore === 0) {
            return [
                'primary' => 'other',
                'secondary' => [],
                'confidence' => 'high',
                'scores' => $scores,
            ];
        }

        // Secondary: kategori lain dengan skor >= 50% dari top
        $secondary = [];
        foreach ($scores as $cat => $score) {
            if ($cat !== $primary && $score > 0 && $score >= $topScore * 0.5) {
                $secondary[] = $cat;
            }
        }

        // Confidence berdasarkan selisih skor
        $sorted = array_values($scores);
        $delta = $sorted[0] - ($sorted[1] ?? 0);
        $confidence = match (true) {
            $delta >= 3 => 'high',
            $delta >= 1 => 'med',
            default => 'low',
        };

        return [
            'primary' => $primary,
            'secondary' => $secondary,
            'confidence' => $confidence,
            'scores' => $scores,
        ];
    }
}