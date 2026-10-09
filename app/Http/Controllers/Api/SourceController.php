<?php

namespace App\Http\Controllers\Api;

use App\Models\Source;
use Illuminate\Http\JsonResponse;

class SourceController extends Controller
{
    public function index(): JsonResponse
    {
        $sources = Source::withCount('articles')->orderBy('name')->get();

        return response()->json($sources->map(fn (Source $s) => [
            'id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'domain' => $s->domain,
            'enabled' => $s->enabled,
            'playwright_enabled' => $s->playwright_enabled,
            'rate_limit_per_min' => $s->rate_limit_per_min,
            'rss_feeds' => $s->rss_feeds ?? [],
            'articles_count' => $s->articles_count,
        ]));
    }

    public function show(Source $source): JsonResponse
    {
        $source->loadCount('articles');

        return response()->json([
            'id' => $source->id,
            'code' => $source->code,
            'name' => $source->name,
            'domain' => $source->domain,
            'base_url' => $source->base_url,
            'enabled' => $source->enabled,
            'playwright_enabled' => $source->playwright_enabled,
            'rate_limit_per_min' => $source->rate_limit_per_min,
            'rss_feeds' => $source->rss_feeds ?? [],
            'html_selectors' => $source->html_selectors ?? [],
            'articles_count' => $source->articles_count,
        ]);
    }
}
