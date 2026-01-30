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

        $query = Page::query();

        // Accept multiple possible query param names sent by the frontend toggle
        $onlyTrashed = $request->boolean('only_trashed')
            || $request->boolean('onlyDeleted')
            || $request->boolean('trashed')
            || $request->boolean('show_deleted');

        $withTrashed = $request->boolean('with_trashed')
            || $request->boolean('withDeleted')
            || $request->boolean('include_deleted');

        if ($onlyTrashed) {
            $query = $query->onlyTrashed();
        } elseif ($withTrashed) {
            $query = $query->withTrashed();
        }

        $pages = $query
            ->when($request->search, function ($q) use ($request) {
                $term = $request->search;
                $q->where(function ($qq) use ($term) {
                    $qq->where('name', 'like', '%' . $term . '%')
                        ->orWhere('label', 'like', '%' . $term . '%')
                        ->orWhere('slug', 'like', '%' . $term . '%')
                        ->orWhere('contents', 'like', '%' . $term . '%')
                        ->orWhere('meta_title', 'like', '%' . $term . '%')
                        ->orWhere('meta_description', 'like', '%' . $term . '%')
                        ->orWhere('meta_keyword', 'like', '%' . $term . '%');
                });
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
            'name' => 'sometimes|required|string',
            'label' => 'sometimes|nullable|string',
            'album_id' => 'sometimes|nullable|exists:albums,id',
            'contents' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:published,private',
            'meta_title' => 'sometimes|nullable|string',
            'meta_description' => 'sometimes|nullable|string',
            'meta_keyword' => 'sometimes|nullable|string',
        ]);

        if (array_key_exists('name', $validated)) {
            $validated['slug'] = Str::slug($validated['name']);
        }

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

    public function destroy(int $id)
    {
        $page = Page::findOrFail($id);

        // Ensure frontend recognizes this as deleted by setting status
        try {
            $page->update(['status' => 'deleted']);
        } catch (\Exception $e) {
            // ignore if update fails for unexpected reasons
        }

        // Soft delete (Page uses SoftDeletes). If you want hard delete, use forceDelete().
        $page->delete();

        return response()->json([
            'message' => 'Page deleted'
        ]);
    }

    public function restore(Request $request)
    {
        $ids = $request->input('ids') ?? $request->input('id');

        if (is_null($ids)) {
            return response()->json(['message' => 'No id(s) provided'], 422);
        }

        $ids = is_array($ids) ? $ids : [$ids];

        $pages = Page::withTrashed()->whereIn('id', $ids)->get();

        $restoredCount = 0;

        foreach ($pages as $p) {
            if ($p->trashed()) {
                $p->restore();

                // If status was 'deleted', move it back to 'draft' so it no longer appears as deleted
                if ($p->status === 'deleted') {
                    try {
                        $p->update(['status' => 'draft']);
                    } catch (\Exception $e) {
                        // ignore update failures
                    }
                }

                $restoredCount++;
            }
        }

        return response()->json([
            'message' => 'Pages restored',
            'restored_count' => $restoredCount
        ]);
    }

}
