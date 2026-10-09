<?php

namespace App\Http\Controllers\Api;

use App\Models\CrawlJob;
use App\Services\CrawlOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrawlJobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['sometimes', 'nullable', 'regex:/^\d{4}-\d{2}$/'],
            'per_page' => ['sometimes', 'integer', 'min:5', 'max:100'],
        ]);

        $jobs = CrawlJob::query()
            ->when($validated['period'] ?? null, fn ($q, $period) => $q->where('period', $period))
            ->withCount('articles')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json($jobs);
    }

    public function periods(): JsonResponse
    {
        return response()->json(
            CrawlJob::query()
                ->distinct()
                ->orderByDesc('period')
                ->pluck('period')
                ->values(),
        );
    }

    public function show(CrawlJob $job): JsonResponse
    {
        $job->load(['sourceLogs.source', 'failures']);

        return response()->json($job);
    }

    public function run(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'window' => ['sometimes', 'in:main,addon'],
            'trigger' => ['sometimes', 'in:cron,manual'],
        ]);

        $orchestrator = app(CrawlOrchestrator::class);
        $job = $orchestrator->run(
            $validated['period'],
            $validated['window'] ?? 'main',
            $validated['trigger'] ?? 'manual'
        );

        return response()->json([
            'message' => 'Crawl job diproses',
            'job' => $job,
        ], 202);
    }
}
