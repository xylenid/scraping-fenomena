<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['sometimes', 'nullable', 'regex:/^\d{4}-\d{2}$/'],
            'source' => ['sometimes', 'nullable', 'string', 'exists:sources,code'],
            'category' => ['sometimes', 'nullable', Rule::in(Article::CATEGORIES)],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'needs_review' => ['sometimes', 'boolean'],
            'is_addon' => ['sometimes', 'boolean'],
            'per_page' => ['sometimes', 'integer', 'min:5', 'max:100'],
        ]);

        $query = Article::query()
            ->with(['source:id,code,name', 'crawlJob:id,period,window_type'])
            ->withCount('entities');

        $query->when(
            $validated['period'] ?? null,
            fn ($q, $period) => $q->where('period_target', $period),
        );

        $query->when(
            $validated['source'] ?? null,
            fn ($q, $code) => $q->whereHas('source', fn ($s) => $s->where('code', $code)),
        );

        $query->when(
            $validated['category'] ?? null,
            fn ($q, $category) => $q->where('category_primary', $category),
        );

        $query->when(
            $request->boolean('needs_review'),
            fn ($q) => $q->where('needs_review', true),
        );

        $query->when(
            $request->boolean('is_addon'),
            fn ($q) => $q->where('is_addon', true),
        );

        $query->when(
            $validated['q'] ?? null,
            function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('title', 'ilike', "%{$term}%")
                        ->orWhere('author', 'ilike', "%{$term}%");
                });
            },
        );

        $articles = $query
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 20)
            ->withQueryString();

        return response()->json($articles);
    }

    public function show(Article $article): JsonResponse
    {
        $article->load(['source', 'crawlJob', 'content', 'entities']);

        return response()->json($article);
    }
}
