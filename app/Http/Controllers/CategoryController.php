<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryIndexRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function index(CategoryIndexRequest $request, CategoryService $service)
    {
        $categories = $service->paginate($request->validated(), (int) $request->input('per_page', 15));
        return ApiResponse::success(CategoryResource::collection($categories->getCollection())->resolve($request), 200, [
            'pagination' => ['current_page' => $categories->currentPage(), 'per_page' => $categories->perPage(), 'total' => $categories->total(), 'last_page' => $categories->lastPage(), 'from' => $categories->firstItem(), 'to' => $categories->lastItem()],
        ]);
    }

    public function store(StoreCategoryRequest $request, CategoryService $service)
    {
        $category = $service->create($request->validated(), $request->auth_user_id);
        Log::info('Category created', ['category_id' => $category->id]);
        return ApiResponse::success((new CategoryResource($category))->resolve($request), 201);
    }

    public function show($id, CategoryService $service)
    {
        $category = $service->find((int) $id);
        return $category
            ? ApiResponse::success((new CategoryResource($category))->resolve(request()))
            : ApiResponse::error('NOT_FOUND', 'Category not found.', 404);
    }

    public function update(UpdateCategoryRequest $request, $id, CategoryService $service)
    {
        $category = Category::find($id);

        if (!$category) {
            return ApiResponse::error('NOT_FOUND', 'Category not found.', 404);
        }
        $category = $service->update($category, $request->validated(), $request->auth_user_id);

        Log::info('Category updated', ['category_id' => $category->id]);

        return ApiResponse::success((new CategoryResource($category))->resolve($request));
    }

    public function destroy(Request $request, $id, CategoryService $service)
    {
        $category = Category::find($id);
        if (!$category) {
            return ApiResponse::error('NOT_FOUND', 'Category not found.', 404);
        }
        $service->delete($category, $request->auth_user_id);
        Log::info('Category deleted', ['category_id' => $id]);

        return ApiResponse::success(['deleted' => true]);
    }
}
