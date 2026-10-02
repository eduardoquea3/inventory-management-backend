<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\StoreStockMovementRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\ProductService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(ProductIndexRequest $request, ProductService $service)
    {
        $products = $service->paginate($request->query(), (int) $request->input('per_page', 15));
        $data = ProductResource::collection($products->getCollection())->resolve($request);
        return ApiResponse::success($data, 200, [
            'pagination' => [
                'current_page' => $products->currentPage(), 'per_page' => $products->perPage(),
                'total' => $products->total(), 'last_page' => $products->lastPage(),
                'from' => $products->firstItem(), 'to' => $products->lastItem(),
            ],
        ]);
    }

    public function store(StoreProductRequest $request, ProductService $service)
    {
        $product = $service->create($request->validated(), $request->auth_user_id);
        Log::info('Product created', ['product_id' => $product->id, 'payload' => $request->all()]);
        return ApiResponse::success((new ProductResource($product))->resolve($request), 201);
    }

    public function show($id, ProductService $service)
    {
        $product = $service->find((int) $id);
        if (!$product) {
            return ApiResponse::error('NOT_FOUND', 'Product not found.', 404);
        }
        $product->setAttribute('category_name', $product->category?->name);
        $product->setAttribute('total_movements', $product->stock_movements_count);
        return ApiResponse::success((new ProductResource($product))->resolve(request()));
    }

    public function update(UpdateProductRequest $request, $id, ProductService $service)
    {
        $product = Product::find($id);
        if (!$product) {
            return ApiResponse::error('NOT_FOUND', 'Product not found.', 404);
        }
        $product = $service->update($product, $request->validated(), $request->auth_user_id);
        Log::info('Product updated', ['product_id' => $product->id, 'payload' => $request->all()]);
        return ApiResponse::success((new ProductResource($product))->resolve($request));
    }

    public function destroy(Request $request, $id, ProductService $service)
    {
        $product = Product::find($id);

        if (!$product) {
            return ApiResponse::error('NOT_FOUND', 'Product not found.', 404);
        }
        if (!$service->delete($product, $request->auth_user_id)) {
            return ApiResponse::error('PRODUCT_HAS_STOCK_HISTORY', 'Product cannot be deleted while stock movement history exists.', 409);
        }
        Log::info('Product deleted', ['product_id' => $id]);

        return ApiResponse::success(['deleted' => true]);
    }

    public function stockMovements($id)
    {
        if (!Product::whereKey($id)->exists()) {
            return ApiResponse::error('NOT_FOUND', 'Product not found.', 404);
        }
        $movements = StockMovement::where('product_id', $id)->orderBy('id', 'desc')->get();
        return ApiResponse::success(StockMovementResource::collection($movements)->resolve(request()));
    }

    public function storeStockMovement(StoreStockMovementRequest $request, $id, ProductService $service)
    {
        $product = Product::find($id);
        if (!$product) {
            return ApiResponse::error('NOT_FOUND', 'Product not found.', 404);
        }
        [$product, $movement] = $service->recordMovement($product, $request->validated(), $request->auth_user_id);

        Log::info('Stock movement registered', ['movement_id' => $movement->id]);

        return ApiResponse::success(['product' => (new ProductResource($product))->resolve($request), 'movement' => (new StockMovementResource($movement))->resolve($request)]);
    }
}
