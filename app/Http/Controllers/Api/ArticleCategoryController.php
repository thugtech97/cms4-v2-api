<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ArticleCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = ArticleCategory::withCount('articles')
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            })
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = ArticleCategory::create([
            'name'    => $validated['name'],
            'slug'    => Str::slug($validated['name']),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'data'    => $category,
        ], 201);
    }

    public function show(ArticleCategory $category)
    {
        return response()->json([
            'data' => $category
        ]);
    }

    public function update(Request $request, ArticleCategory $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }
}
