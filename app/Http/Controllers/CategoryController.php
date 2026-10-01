<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $categories = Category::orderByDesc('created_at')->orderByDesc('id')
            ->paginate((int) ($validated['per_page'] ?? 15));

        return \Illuminate\Http\Resources\Json\JsonResource::collection($categories);
    }

    public function store(Request $request)
    {
        if (!$request->name) {
            return response()->json(['error' => 'name required'], 422);
        }

        $category = Category::create($request->all());
        Log::info('Category created', ['category_id' => $category->id]);

        return response()->json($category, 201);
    }

    public function show($id)
    {
        return response()->json(Category::find($id));
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'No category'], 404);
        }

        $category->fill($request->all());
        $category->save();

        Log::info('Category updated', ['category_id' => $category->id]);

        return response()->json(['updated' => true, 'category' => $category]);
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['error' => 'not found'], 404);
        }

        $category->delete();
        Log::info('Category deleted', ['category_id' => $id]);

        return response()->json(['ok' => true]);
    }
}
