<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\ProductCategory;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = ProductCategory::query()
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%");
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 50));

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        // Accept a few common field names from different frontends
        $normalizedName = $request->input('name')
            ?? $request->input('product_category_name')
            ?? $request->input('category_name');

        if (!is_null($normalizedName) && $request->input('name') !== $normalizedName) {
            $request->merge(['name' => $normalizedName]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $slug = $this->uniqueSlug(Str::slug($validated['name']));

        $category = ProductCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 2;

        while (ProductCategory::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i;
            $i++;
        }

        return $slug;
    }
}
