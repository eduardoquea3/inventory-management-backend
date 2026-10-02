<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiResponseContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_api_errors_use_the_standard_envelope(): void
    {
        $this->getJson('/api/categories')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'UNAUTHENTICATED')
            ->assertJsonStructure(['error' => ['code', 'message']]);
    }

    public function test_category_crud_success_responses_use_the_standard_envelope(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\LegacyTokenAuth::class);

        $created = $this->postJson('/api/categories', ['name' => 'Contract category'])
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Contract category');

        $id = $created->json('data.id');
        $this->getJson('/api/categories/' . $id)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $id);
    }

    public function test_form_request_validation_uses_standard_error_envelope(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\LegacyTokenAuth::class);

        $this->postJson('/api/categories', [])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['name']]]);
    }

    public function test_login_me_and_logout_work_with_standard_api_envelopes(): void
    {
        User::create([
            'name' => 'API auth test user',
            'email' => 'api-auth-test@example.test',
            'password' => Hash::make('secret-password'),
        ]);

        $login = $this->postJson('/api/login', ['email' => 'api-auth-test@example.test', 'password' => 'secret-password'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'api-auth-test@example.test');
        $token = $login->json('data.token');

        $this->getJson('/api/me', ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'api-auth-test@example.test')
            ->assertJsonMissingPath('data.password')
            ->assertJsonMissingPath('data.api_token');

        $this->postJson('/api/logout', [], ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.logged_out', true);

        $this->getJson('/api/me', ['Authorization' => 'Bearer ' . $token])
            ->assertUnauthorized()
            ->assertJsonPath('error.code', 'UNAUTHENTICATED');
    }

    public function test_product_write_is_audited_and_invalidates_cached_catalog(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\LegacyTokenAuth::class);

        $this->getJson('/api/products?q=cache-invalidation-product')->assertOk()->assertJsonPath('meta.pagination.total', 0);

        $created = $this->postJson('/api/products', [
            'name' => 'cache-invalidation-product',
            'price' => 9.99,
            'stock' => 5,
        ])->assertCreated()->assertJsonPath('success', true);
        $id = $created->json('data.id');

        $this->getJson('/api/products?q=cache-invalidation-product')
            ->assertOk()
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.id', $id);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created', 'entity_type' => 'product', 'entity_id' => $id,
        ]);
    }

    public function test_category_delete_detaches_products_and_is_audited(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\LegacyTokenAuth::class);
        $created = $this->postJson('/api/categories', ['name' => 'Category delete policy'])
            ->assertCreated();
        $categoryId = $created->json('data.id');
        $category = Category::findOrFail($categoryId);
        $product = Product::create(['name' => 'Detached product', 'category_id' => $category->id]);

        $this->putJson('/api/categories/' . $categoryId, ['name' => 'Updated category policy'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated category policy');

        $this->deleteJson('/api/categories/' . $categoryId)
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'category_id' => null]);
        foreach (['created', 'updated', 'deleted'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['action' => $action, 'entity_type' => 'category', 'entity_id' => $categoryId]);
        }
    }

    public function test_product_crud_validation_and_audit_lifecycle(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\LegacyTokenAuth::class);

        $this->postJson('/api/products', ['name' => 'Invalid price product', 'price' => -1])
            ->assertUnprocessable()
            ->assertJsonPath('error.code', 'VALIDATION_FAILED')
            ->assertJsonStructure(['error' => ['details' => ['price']]]);

        $created = $this->postJson('/api/products', ['name' => 'Audited product', 'price' => 12, 'stock' => 5])
            ->assertCreated();
        $productId = $created->json('data.id');

        $this->putJson('/api/products/' . $productId, ['name' => 'Updated audited product'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated audited product');

        $this->deleteJson('/api/products/' . $productId)
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        foreach (['created', 'updated', 'deleted'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['action' => $action, 'entity_type' => 'product', 'entity_id' => $productId]);
        }
    }
}
