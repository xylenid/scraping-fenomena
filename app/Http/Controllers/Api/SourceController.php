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
            'articles_count' => $s->articles_count,
        ]));
    }

    public function show(Source $source): JsonResponse
    {
        return response()->json($source->loadCount('articles'));
    }
}