<?php

namespace App\Http\Controllers\Api;

use App\Models\Article;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ArticleCategory;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Api\ArticleController;

class ArticleController extends Controller
{

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        $articles = Article::query()
            ->with('category:id,name') // relation required
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            })
            ->latest('updated_at')
            ->paginate($perPage);

        return response()->json($articles);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|integer',
            'date' => 'required|date',
            'name' => 'required|string|max:255',
            'contents' => 'required|string',
            'teaser' => 'required|string',
            'status' => 'required|in:private,published',
            'is_featured' => 'boolean',

            'meta_title' => 'nullable|string',
            'meta_keyword' => 'nullable|string',
            'meta_description' => 'nullable|string',

            'banner' => 'nullable|image|max:2048',
            'thumbnail' => 'nullable|image|max:1024',
        ]);

        // 🔹 Upload banner
        if ($request->hasFile('banner')) {
            $validated['image_url'] =
                $request->file('banner')->store('articles/banners', 'public');
        }

        // 🔹 Upload thumbnail
        if ($request->hasFile('thumbnail')) {
            $validated['thumbnail_url'] =
                $request->file('thumbnail')->store('articles/thumbnails', 'public');
        }

        $article = Article::create([
            'category_id' => $validated['category_id'],
            'slug' => Str::slug($validated['name']),
            'date' => $validated['date'],
            'name' => $validated['name'],
            'contents' => $validated['contents'],
            'teaser' => $validated['teaser'],
            'status' => $validated['status'],
            'is_featured' => $validated['is_featured'] ?? 0,
            'image_url' => $validated['image_url'] ?? null,
            'thumbnail_url' => $validated['thumbnail_url'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_keyword' => $validated['meta_keyword'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Article created successfully',
            'data' => $article
        ], 201);
    }

    public function show(Article $article)
    {
        $article->load('category');

        return response()->json([
            'data' => $article
        ]);
    }

    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'category_id' => 'required|integer',
            'date' => 'required|date',
            'name' => 'required|string|max:255',
            'contents' => 'required|string',
            'teaser' => 'required|string',
            'status' => 'required|in:private,published',
            'is_featured' => 'boolean',

            'meta_title' => 'nullable|string',
            'meta_keyword' => 'nullable|string',
            'meta_description' => 'nullable|string',

            'banner' => 'nullable|image|max:2048',
            'thumbnail' => 'nullable|image|max:1024',
        ]);

        // 🔹 Replace banner if uploaded
        if ($request->hasFile('banner')) {
            if ($article->image_url) {
                Storage::disk('public')->delete($article->image_url);
            }

            $validated['image_url'] =
                $request->file('banner')->store('articles/banners', 'public');
        }

        // 🔹 Replace thumbnail if uploaded
        if ($request->hasFile('thumbnail')) {
            if ($article->thumbnail_url) {
                Storage::disk('public')->delete($article->thumbnail_url);
            }

            $validated['thumbnail_url'] =
                $request->file('thumbnail')->store('articles/thumbnails', 'public');
        }

        $article->update([
            'category_id' => $validated['category_id'],
            'date' => $validated['date'],
            'name' => $validated['name'],
            'contents' => $validated['contents'],
            'teaser' => $validated['teaser'],
            'status' => $validated['status'],
            'is_featured' => $validated['is_featured'] ?? 0,
            'image_url' => $validated['image_url'] ?? $article->image_url,
            'thumbnail_url' => $validated['thumbnail_url'] ?? $article->thumbnail_url,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_keyword' => $validated['meta_keyword'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Article updated successfully',
            'data' => $article
        ]);
    }

    public function fetch_categories()
    {
        return response()->json([
            'data' => ArticleCategory::orderBy('name')->get()
        ]);
    }
}
