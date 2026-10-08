<?php

namespace App\Http\Controllers\Api;

use App\Models\CrawlJob;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class InternalScheduleController extends Controller
{
    /**
     * Endpoint yang dipanggil oleh scheduler eksternal (GitHub Actions / cron-job.org).
     * Menentukan window (main/addon) berdasarkan tanggal WIB, cek idempotency,
     * lalu menjalankan crawl:monthly setelah response dikirim.
     */
    public function runSchedule(Request $request): JsonResponse
    {
        $token = config('services.cron_token');
        if (blank($token) || !hash_equals($token, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Token tidak valid'], 403);
        }

        $now = Carbon::now('Asia/Jakarta');
        $window = $request->input('window');
        $period = $request->input('period');
        $force = $request->boolean('force');

        // Jika window tidak dispesifikkan, tentukan dari tanggal (grace 1 hari
        // untuk menoleransi keterlambatan trigger scheduler eksternal).
        if (blank($window)) {
            $day = (int) $now->day;
            $window = match (true) {
                in_array($day, [1, 2], true) => 'main',
                in_array($day, [10, 11], true) => 'addon',
                default => null,
            };
        }

        if (!in_array($window, ['main', 'addon'], true)) {
            return response()->json([
                'due' => false,
                'message' => 'Tidak ada jadwal crawl untuk hari ini',
                'date' => $now->toDateString(),
            ]);
        }

        if (blank($period)) {
            $period = $now->copy()->subMonth()->format('Y-m');
        }

        if (!preg_match('/^\d{4}-\d{2}$/', $period)) {
            return response()->json(['message' => 'Format period harus YYYY-MM'], 422);
        }

        // Idempotency: lewati jika window yang sama untuk periode ini baru saja
        // berjalan/berhasil. Status partial/failed diizinkan agar bisa retry.
        $recent = CrawlJob::where('period', $period)
            ->where('window_type', $window)
            ->whereIn('status', ['queued', 'running', 'completed'])
            ->where('started_at', '>=', $now->copy()->subHours(48))
            ->exists();

        if ($recent && !$force) {
            return response()->json([
                'due' => false,
                'message' => "Crawl {$window} untuk periode {$period} sudah berjalan baru-baru ini, dilewati",
                'period' => $period,
                'window' => $window,
            ]);
        }

        Log::info("Schedule endpoint: menjalankan crawl {$window} periode {$period}");

        // Jalankan setelah response terkirim agar panggilan HTTP tidak timeout.
        app()->terminating(function () use ($period, $window) {
            try {
                Artisan::call('crawl:monthly', [
                    '--period' => $period,
                    '--window' => $window,
                    '--trigger' => 'cron',
                ]);
            } catch (\Throwable $e) {
                Log::error("Crawl gagal via schedule endpoint: {$e->getMessage()}");
            }
        });

        return response()->json([
            'due' => true,
            'message' => "Crawl {$window} periode {$period} dijalankan",
            'period' => $period,
            'window' => $window,
            'started_at' => $now->toIso8601String(),
        ], 202);
    }
}
