<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use DatabaseTransactions;

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
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'per_page', 'total', 'last_page']])
            ->assertJsonPath('meta.per_page', 15)
            ->assertJsonPath('meta.total', 9)
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
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_categories_return_standard_paginated_results(): void
    {
        foreach (range(1, 3) as $number) {
            Category::create(['name' => 'Pagination category ' . $number]);
        }

        $this->getJson('/api/categories?per_page=2')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta' => ['current_page', 'per_page', 'total', 'last_page']])
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_invalid_pagination_parameters_return_json_validation_errors(): void
    {
        foreach (['page=0', 'page=abc', 'per_page=0', 'per_page=101', 'per_page=1.5'] as $query) {
            $this->getJson('/api/categories?' . $query)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(str_starts_with($query, 'page=') ? 'page' : 'per_page');
        }
    }
}
