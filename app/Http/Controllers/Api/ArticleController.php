<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Article::query()
            ->with(['source:id,code,name', 'crawlJob:id,period,window_type'])
            ->withCount('entities');

        // Filter periode
        if ($request->filled('period')) {
            $query->where('period_target', $request->input('period'));
        }

        // Filter sumber
        if ($request->filled('source')) {
            $query->where('source_id', $request->input('source'));
        }

        // Filter kategori
        if ($request->filled('category')) {
            $query->where('category_primary', $request->input('category'));
        }

        // Filter needs_review
        if ($request->boolean('needs_review')) {
            $query->where('needs_review', true);
        }

        // Filter addon
        if ($request->boolean('is_addon')) {
            $query->where('is_addon', true);
        }

        // Pencarian teks
        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'ilike', '%' . $request->input('q') . '%')
                    ->orWhere('author', 'ilike', '%' . $request->input('q') . '%');
            });
        }

        $articles = $query
            ->orderByDesc('published_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($articles);
    }

    public function show(Article $article): JsonResponse
    {
        $article->load(['source', 'crawlJob', 'content', 'entities']);

        return response()->json($article);
    }
}