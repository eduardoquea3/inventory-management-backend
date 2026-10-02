<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\ProductService;
use App\Support\CatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\LegacyTokenAuth::class);
    }

    public function test_products_return_paginated_results_with_filters_and_product_fields(): void
    {
        $category = Category::create(['name' => 'Pagination category']);
        foreach (range(1, 17) as $number) {
            Product::create([
                'name' => 'Pagination item ' . $number,
                'category_id' => $category->id,
                'status' => $number % 2,
            ]);
        }
        Product::create(['name' => 'Other category item', 'status' => 1]);

        $response = $this->getJson('/api/products?q=Pagination&category_id=' . $category->id . '&status=1');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta' => ['pagination' => ['current_page', 'per_page', 'total', 'last_page']]])
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.pagination.per_page', 15)
            ->assertJsonPath('meta.pagination.total', 9)
            ->assertJsonPath('data.0.category.id', $category->id)
            ->assertJsonStructure(['data' => [['category', 'total_movements']]]);
    }

    public function test_products_support_custom_page_size_and_navigation(): void
    {
        foreach (range(1, 5) as $number) {
            Product::create(['name' => 'Custom page item ' . $number]);
        }

        $this->getJson('/api/products?per_page=2&page=2')
            ->assertOk()
            ->assertJsonPath('meta.pagination.current_page', 2)
            ->assertJsonPath('meta.pagination.per_page', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_products_filter_by_price_and_stock_and_sort_by_allowed_column(): void
    {
        Product::create(['name' => 'Filter case cheap', 'price' => 5, 'stock' => 2, 'status' => 1]);
        Product::create(['name' => 'Filter case middle', 'price' => 15, 'stock' => 8, 'status' => 1]);
        Product::create(['name' => 'Filter case expensive', 'price' => 25, 'stock' => 20, 'status' => 1]);

        $this->getJson('/api/products?q=Filter%20case&min_price=10&max_price=20&min_stock=5&sort_by=price&sort_direction=asc')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Filter case middle');
    }

    public function test_categories_filter_by_name_and_status_and_sort_by_name(): void
    {
        Category::create(['name' => 'Zulu active', 'status' => 1]);
        Category::create(['name' => 'Alpha active', 'status' => 1]);
        Category::create(['name' => 'Alpha inactive', 'status' => 0]);

        $this->getJson('/api/categories?q=Alpha&status=1&sort_by=name&sort_direction=asc')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.name', 'Alpha active');
    }

    public function test_product_filter_ranges_and_sort_options_are_validated(): void
    {
        $this->getJson('/api/products?min_price=20&max_price=10')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['min_price']]]);

        $this->getJson('/api/products?sort_by=category_id')
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['sort_by']]]);
    }

    public function test_product_page_eager_loads_category_and_movement_count_without_n_plus_one(): void
    {
        $category = Category::create(['name' => 'Query count category']);
        foreach (range(1, 5) as $number) {
            $product = Product::create(['name' => 'Query count product ' . $number, 'category_id' => $category->id]);
            StockMovement::create(['product_id' => $product->id, 'type' => 'entrada', 'quantity' => 1]);
        }

        CatalogCache::invalidate();
        DB::flushQueryLog();
        DB::enableQueryLog();
        app(ProductService::class)->paginate(['q' => 'Query count product', 'per_page' => 15]);
        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(3, $queryCount, 'Product listing should query products, count pagination rows, and eager-load categories without per-row queries.');
    }

    public function test_categories_return_standard_paginated_results(): void
    {
        foreach (range(1, 3) as $number) {
            Category::create(['name' => 'Pagination category ' . $number]);
        }

        $this->getJson('/api/categories?per_page=2')
            ->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta' => ['pagination' => ['current_page', 'per_page', 'total', 'last_page']]])
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.pagination.per_page', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_invalid_pagination_parameters_return_json_validation_errors(): void
    {
        foreach (['page=0', 'page=abc', 'per_page=0', 'per_page=101', 'per_page=1.5'] as $query) {
            $this->getJson('/api/categories?' . $query)
                ->assertUnprocessable()
                ->assertJsonPath('success', false)
                ->assertJsonPath('error.code', 'VALIDATION_FAILED')
                ->assertJsonStructure(['error' => ['details']]);
        }
    }
}
