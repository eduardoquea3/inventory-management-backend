<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\CatalogCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(private readonly AuditService $audit) {}

    public function paginate(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return CatalogCache::remember('products', $filters + ['per_page' => $perPage], function () use ($filters, $perPage) {
            $query = Product::query()->with('category')->withCount('stockMovements');
            if (!empty($filters['q'])) $query->where('name', 'like', '%' . $filters['q'] . '%');
            if (!empty($filters['category_id'])) $query->where('category_id', $filters['category_id']);
            if (array_key_exists('status', $filters)) $query->where('status', $filters['status']);
            if (isset($filters['min_price'])) $query->where('price', '>=', $filters['min_price']);
            if (isset($filters['max_price'])) $query->where('price', '<=', $filters['max_price']);
            if (isset($filters['min_stock'])) $query->where('stock', '>=', $filters['min_stock']);
            if (isset($filters['max_stock'])) $query->where('stock', '<=', $filters['max_stock']);

            $sortBy = $filters['sort_by'] ?? 'created_at';
            $direction = $filters['sort_direction'] ?? 'desc';
            return $query->orderBy($sortBy, $direction)->orderBy('id', $direction)->paginate($perPage);
        });
    }

    public function create(array $data, ?int $userId): Product
    {
        $product = DB::transaction(function () use ($data, $userId) {
            $product = Product::create($data);
            $this->audit->record($userId, 'created', 'product', $product->id, null, $product->getAttributes());
            return $product;
        });
        CatalogCache::invalidate();
        return $product;
    }

    public function update(Product $product, array $data, ?int $userId): Product
    {
        $product = DB::transaction(function () use ($product, $data, $userId) {
            $oldValues = $product->getAttributes();
            $product->update($data);
            $product->refresh();
            $this->audit->record($userId, 'updated', 'product', $product->id, $oldValues, $product->getAttributes());
            return $product;
        });
        CatalogCache::invalidate();
        return $product;
    }

    public function delete(Product $product, ?int $userId): bool
    {
        $deleted = DB::transaction(function () use ($product, $userId) {
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->first();
            if (!$lockedProduct || $lockedProduct->stockMovements()->exists()) {
                return false;
            }

            $oldValues = $lockedProduct->getAttributes();
            $this->audit->record($userId, 'deleted', 'product', $lockedProduct->id, $oldValues, null);
            $lockedProduct->delete();
            return true;
        });
        if ($deleted) CatalogCache::invalidate();
        return $deleted;
    }

    public function find(int $id): ?Product
    {
        return Product::with('category')->withCount('stockMovements')->find($id);
    }

    public function recordMovement(Product $product, array $data, ?int $userId): array
    {
        $result = DB::transaction(function () use ($product, $data, $userId) {
            $lockedProduct = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $quantity = (int) $data['quantity'];
            if ($data['type'] === 'salida' && $quantity > $lockedProduct->stock) {
                throw new InsufficientStockException((int) $lockedProduct->stock, $quantity);
            }
            $oldStock = (int) $lockedProduct->stock;
            $lockedProduct->stock += $data['type'] === 'salida' ? -$quantity : $quantity;
            $lockedProduct->save();
            $movement = StockMovement::create([
                'product_id' => $lockedProduct->id,
                'type' => $data['type'],
                'quantity' => $quantity,
                'reason' => $data['reason'] ?? null,
                'user_id' => $userId,
            ]);
            $this->audit->record($userId, 'created', 'stock_movement', $movement->id, null, [
                'movement' => $movement->getAttributes(),
                'product_stock_before' => $oldStock,
                'product_stock_after' => (int) $lockedProduct->stock,
            ]);

            return [$lockedProduct->refresh(), $movement];
        });
        CatalogCache::invalidate();
        return $result;
    }
}
