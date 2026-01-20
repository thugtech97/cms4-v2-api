<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LayoutPreset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LayoutPresetController extends Controller
{
    public function index()
    {
        return LayoutPreset::orderByDesc('created_at')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'content' => 'required|string',
            'is_active' => 'boolean',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // Handle thumbnail upload
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store(
                'layout-presets',
                'public'
            );
            $data['thumbnail'] = $path; // /storage/...
        }

        return LayoutPreset::create($data);
    }

    public function update(Request $request, $id)
    {
        $preset = LayoutPreset::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'nullable|string|max:255',
            'content' => 'required|string',
            'is_active' => 'boolean',
            'thumbnail' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('thumbnail')) {
            // delete old thumbnail if exists
            if ($preset->thumbnail) {
                $oldPath = $preset->thumbnail;
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('thumbnail')->store(
                'layout-presets',
                'public'
            );
            $data['thumbnail'] = $path;
        }

        $preset->update($data);

        return $preset;
    }

    public function destroy($id)
    {
        $preset = LayoutPreset::findOrFail($id);

        if ($preset->thumbnail) {
            $path = $preset->thumbnail;
            Storage::disk('public')->delete($path);
        }

        $preset->delete();

        return response()->json(['success' => true]);
    }
}
