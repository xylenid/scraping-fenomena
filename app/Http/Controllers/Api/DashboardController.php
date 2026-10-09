<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use App\Models\CrawlJob;
use App\Models\Source;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $period = $request->query('period');

        $articlesQuery = Article::query();
        if ($period) {
            $articlesQuery->where('period_target', $period);
        }

        $totalArticles = (clone $articlesQuery)->count();

        $categoryCounts = (clone $articlesQuery)
            ->selectRaw('category_primary, count(*) as total')
            ->groupBy('category_primary')
            ->pluck('total', 'category_primary');

        $byCategory = collect(Article::CATEGORIES)
            ->mapWithKeys(fn (string $category) => [$category => (int) ($categoryCounts[$category] ?? 0)]);

        $bySource = (clone $articlesQuery)
            ->selectRaw('source_id, count(*) as total')
            ->groupBy('source_id')
            ->orderByDesc('total')
            ->with('source:id,code,name')
            ->get()
            ->map(fn ($row) => [
                'source' => $row->source?->name ?? "Source #{$row->source_id}",
                'code' => $row->source?->code,
                'total' => $row->total,
            ])
            ->values();

        $needsReview = (clone $articlesQuery)->where('needs_review', true)->count();
        $addonCount = (clone $articlesQuery)->where('is_addon', true)->count();

        $latestJob = CrawlJob::latest('id')->first();

        return response()->json([
            'period' => $period,
            'periods' => Article::query()
                ->whereNotNull('period_target')
                ->distinct()
                ->orderByDesc('period_target')
                ->pluck('period_target'),
            'total_articles' => $totalArticles,
            'by_category' => $byCategory,
            'by_source' => $bySource,
            'needs_review' => $needsReview,
            'addon_articles' => $addonCount,
            'latest_job' => $latestJob ? [
                'id' => $latestJob->id,
                'period' => $latestJob->period,
                'window_type' => $latestJob->window_type,
                'status' => $latestJob->status,
                'started_at' => $latestJob->started_at,
                'finished_at' => $latestJob->finished_at,
                'stats' => $latestJob->stats,
            ] : null,
            'total_sources' => Source::where('enabled', true)->count(),
        ]);
    }
}
