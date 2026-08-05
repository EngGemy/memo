<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Category::withCount('videos')->orderBy('position')->get()->map->forApi()->values()
        );
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(
            Category::create($request->validate([
                'name'    => ['required','string','max:60'],
                'name_ar' => ['nullable','string','max:60'],
                'slug'    => ['nullable','string','max:40','alpha_dash','unique:categories,slug'],
            ]))->forApi(),
            201
        );
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $category->update($request->validate([
            'name'      => ['sometimes','string','max:60'],
            'name_ar'   => ['sometimes','nullable','string','max:60'],
            'position'  => ['sometimes','integer','min:0','max:999'],
            'is_active' => ['sometimes','boolean'],
        ]));

        return response()->json($category->fresh()->forApi());
    }

    /**
     * Videos are detached, not deleted. Removing a track should never take
     * the content with it - that is a very expensive misclick.
     */
    public function destroy(Category $category): JsonResponse
    {
        $detached = $category->videos()->update(['category_id' => null]);
        $category->delete();

        return response()->json(['deleted' => true, 'videos_detached' => $detached]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'order'   => ['required','array'],
            'order.*' => ['integer','exists:categories,id'],
        ]);

        foreach ($data['order'] as $i => $id) {
            Category::where('id', $id)->update(['position' => $i + 1]);
        }

        return response()->json(['ok' => true]);
    }
}
