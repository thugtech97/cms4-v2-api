<?php

namespace App\Http\Controllers\Api;

use App\Models\Album;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumResource;
use Illuminate\Support\Facades\Storage;

class AlbumController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->per_page ?? 10;

        $albums = Album::query()
            ->where('id', '!=', 1) // 🚫 exclude Home Banner
            ->withCount('banners')
            ->when($request->search, function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%');
            })
            ->latest()
            ->paginate($perPage);

        return AlbumResource::collection($albums);
    }

    public function show(Album $album)
    {
        return response()->json(
            $album->load('banners')
        );
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {

            $album = Album::create([
                'name' => $request->name,
                'transition_in' => $request->transition_in,
                'transition_out' => $request->transition_out,
                'transition' => $request->transition,
                'type' => 'sub_banner',
                'banner_type' => $request->banner_type,
                'user_id' => auth()->id(),
            ]);

            $this->syncBanners($album, $request->banners ?? []);

            return response()->json($album->load('banners'), 201);
        });
    }

    public function update(Request $request, Album $album)
    {
        return DB::transaction(function () use ($request, $album) {

            $album->update($request->all());

            $this->syncBanners($album, $request->banners ?? []);

            return response()->json($album->load('banners'));
        });
    }

    private function syncBanners(Album $album, array $banners)
    {
        $existingIds = collect($banners)->pluck('id')->filter()->values();
        $removedBanners = Banner::where('album_id', $album->id)
            ->whereNotIn('id', $existingIds)
            ->get();

        foreach ($removedBanners as $removed) {
            if ($removed->image_path && Storage::disk('public')->exists($removed->image_path)) {
                Storage::disk('public')->delete($removed->image_path);
            }

            $removed->delete();
        }

        foreach ($banners as $index => $data) {

            $banner = Banner::updateOrCreate(
                [
                    'id' => $data['id'] ?? null,
                    'album_id' => $album->id,
                ],
                [
                    'title' => $data['title'] ?? null,
                    'description' => $data['description'] ?? null,
                    'alt' => $data['alt'] ?? null,
                    'button_text' => $data['button_text'] ?? null,
                    'url' => $data['url'] ?? null,
                    'order' => $data['order'] ?? $index,
                    'user_id' => auth()->id(),
                ]
            );

            $image = request()->file("banners.$index.image");

            if ($image instanceof UploadedFile) {
                if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
                    Storage::disk('public')->delete($banner->image_path);

                }
                $path = $image->store('banners', 'public');
                $banner->update(['image_path' => $path]);
            }
        }
    }

    public function destroy(Album $album)
    {
        return DB::transaction(function () use ($album) {
            $banners = $album->banners()->get();

            foreach ($banners as $banner) {
                if ($banner->image_path && Storage::disk('public')->exists($banner->image_path)) {
                    Storage::disk('public')->delete($banner->image_path);
                }

                $banner->delete();
            }

            $album->delete();

            return response()->json(null, 204);
        });
    }
}
