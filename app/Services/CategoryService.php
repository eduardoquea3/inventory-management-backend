<?php

namespace App\Services;

use App\Models\Category;
use App\Support\CatalogCache;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    public function __construct(private readonly AuditService $audit) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return CatalogCache::remember('categories', $filters + ['per_page' => $perPage], function () use ($filters, $perPage) {
            $query = Category::query();
            if (!empty($filters['q'])) {
                $query->where('name', 'like', '%' . $filters['q'] . '%');
            }
            if (array_key_exists('status', $filters)) {
                $query->where('status', $filters['status']);
            }

            $sortBy = $filters['sort_by'] ?? 'created_at';
            $direction = $filters['sort_direction'] ?? 'desc';
            return $query->orderBy($sortBy, $direction)
                ->orderBy('id', $direction)
                ->paginate($perPage);
        });
    }

    public function create(array $data, ?int $userId): Category
    {
        $category = DB::transaction(function () use ($data, $userId) {
            $category = Category::create($data);
            $this->audit->record($userId, 'created', 'category', $category->id, null, $category->getAttributes());
            return $category;
        });
        CatalogCache::invalidate();
        return $category;
    }

    public function update(Category $category, array $data, ?int $userId): Category
    {
        $category = DB::transaction(function () use ($category, $data, $userId) {
            $oldValues = $category->getAttributes();
            $category->update($data);
            $category->refresh();
            $this->audit->record($userId, 'updated', 'category', $category->id, $oldValues, $category->getAttributes());
            return $category;
        });
        CatalogCache::invalidate();
        return $category;
    }

    public function delete(Category $category, ?int $userId): void
    {
        DB::transaction(function () use ($category, $userId) {
            $oldValues = $category->getAttributes();
            $this->audit->record($userId, 'deleted', 'category', $category->id, $oldValues, null);
            $category->delete();
        });
        CatalogCache::invalidate();
    }

    public function find(int $id): ?Category
    {
        return Category::find($id);
    }
}
