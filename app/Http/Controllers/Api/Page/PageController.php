<?php

namespace App\Http\Controllers\Api\Page;

use App\Models\Page;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;

class PageController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:255',
            'label'             => 'nullable|string|max:255',
            'parent_page_id'    => 'nullable|exists:pages,id',
            'album_id'          => 'nullable|exists:albums,id',
            'contents'          => 'nullable|string',
            'status'            => 'required|in:published,private,draft',
            'meta_title'        => 'nullable|string|max:255',
            'meta_description'  => 'nullable|string',
            'meta_keyword'      => 'nullable|string',
            'template'          => 'nullable|string|max:255',
        ]);

        $page = Page::create([
            'name'             => $validated['name'],
            'label'            => $validated['label'] ?? null,
            'slug'             => Str::slug($validated['name']),
            'parent_page_id'   => $validated['parent_page_id'] ?? null,
            'album_id'         => $validated['album_id'] ?? null,
            'contents'         => $validated['contents'] ?? null,
            'status'           => $validated['status'],
            'meta_title'       => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'meta_keyword'     => $validated['meta_keyword'] ?? null,
            'template'         => $validated['template'] ?? null,
            'user_id'          => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page created successfully',
            'data'    => $page,
        ], 201);
    }

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);

        $pages = Page::query()
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            })
            ->latest('updated_at')
            ->paginate($perPage);

        return PageResource::collection($pages);
    }

    public function show(int $id)
    {
        $page = Page::findOrFail($id);

        return response()->json([
            'id' => $page->id,
            'name' => $page->name,
            'label' => $page->label,
            'album_id' => $page->album_id, // ✅ ADD THIS
            'contents' => $page->contents,
            'status' => $page->status,
            'meta_title' => $page->meta_title,
            'meta_description' => $page->meta_description,
            'meta_keyword' => $page->meta_keyword,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $page = Page::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'label' => 'nullable|string',
            'album_id' => 'nullable',
            'contents' => 'required|string',
            'status' => 'required|in:published,private',
            'meta_title' => 'nullable|string',
            'meta_description' => 'nullable|string',
            'meta_keyword' => 'nullable|string',
        ]);

        $page->update($validated);

        return response()->json([
            'message' => 'Page updated successfully',
        ]);
    }

    public function pages_menu()
    {
        return response()->json([
            'data' => Page::select('id', 'name', 'label', 'slug')
                ->where('status', 'published')
                ->orderBy('id')
                ->get()
        ]);
    }

}
