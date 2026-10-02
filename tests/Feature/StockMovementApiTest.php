<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_movement_can_be_created_and_is_returned_by_product_history_endpoint(): void
    {
        $user = User::create([
            'name' => 'Stock API test user',
            'email' => 'stock-api-test@example.test',
            'password' => 'test-password',
            'api_token' => 'stock-api-test-token',
        ]);
        $product = Product::create(['name' => 'Stock API test product', 'stock' => 5]);
        $headers = ['Authorization' => 'Bearer ' . $user->api_token];
        $payload = [
            'type' => 'entrada',
            'quantity' => 12,
            'reason' => 'Reposición de inventario',
        ];

        $response = $this->postJson('/api/products/' . $product->id . '/stock-movements', $payload, $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.product.stock', 17)
            ->assertJsonPath('data.movement.type', 'entrada')
            ->assertJsonPath('data.movement.quantity', 12)
            ->assertJsonPath('data.movement.reason', 'Reposición de inventario');

        $movementId = $response->json('data.movement.id');
        $this->assertDatabaseHas('stock_movements', [
            'id' => $movementId,
            'product_id' => $product->id,
            'type' => 'entrada',
            'quantity' => 12,
            'reason' => 'Reposición de inventario',
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'created',
            'entity_type' => 'stock_movement',
            'entity_id' => $movementId,
        ]);

        $this->getJson('/api/products/' . $product->id . '/stock-movements', $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $movementId)
            ->assertJsonPath('data.0.product_id', $product->id)
            ->assertJsonPath('data.0.type', 'entrada')
            ->assertJsonPath('data.0.quantity', 12)
            ->assertJsonPath('data.0.reason', 'Reposición de inventario');
    }

    public function test_stock_movement_endpoints_return_not_found_for_unknown_product(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\LegacyTokenAuth::class);

        $this->postJson('/api/products/999999/stock-movements', [
            'type' => 'entrada',
            'quantity' => 12,
            'reason' => 'Reposición de inventario',
        ])->assertNotFound()->assertJsonPath('error.code', 'NOT_FOUND');

        $this->getJson('/api/products/999999/stock-movements')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'NOT_FOUND');
    }

    public function test_stock_exit_over_available_balance_is_rejected_without_partial_changes(): void
    {
        $user = User::create([
            'name' => 'Stock rule test user',
            'email' => 'stock-rule-test@example.test',
            'password' => 'test-password',
            'api_token' => 'stock-rule-test-token',
        ]);
        $product = Product::create(['name' => 'Insufficient stock product', 'stock' => 3]);

        $this->postJson('/api/products/' . $product->id . '/stock-movements', [
            'type' => 'salida',
            'quantity' => 4,
            'reason' => 'Test insufficient balance',
        ], ['Authorization' => 'Bearer ' . $user->api_token])
            ->assertStatus(409)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error.code', 'INSUFFICIENT_STOCK')
            ->assertJsonPath('error.details.available', 3)
            ->assertJsonPath('error.details.requested', 4);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
        $this->assertDatabaseMissing('stock_movements', ['product_id' => $product->id]);
        $this->assertDatabaseMissing('audit_logs', ['entity_type' => 'stock_movement']);
    }

    public function test_product_with_stock_history_cannot_be_deleted(): void
    {
        $user = User::create([
            'name' => 'Delete policy test user',
            'email' => 'delete-policy-test@example.test',
            'password' => 'test-password',
            'api_token' => 'delete-policy-test-token',
        ]);
        $product = Product::create(['name' => 'Product with history', 'stock' => 0]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'entrada', 'quantity' => 1]);

        $this->deleteJson('/api/products/' . $product->id, [], ['Authorization' => 'Bearer ' . $user->api_token])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PRODUCT_HAS_STOCK_HISTORY');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_valid_stock_exit_updates_balance_and_is_audited(): void
    {
        $user = User::create([
            'name' => 'Stock exit test user',
            'email' => 'stock-exit-test@example.test',
            'password' => 'test-password',
            'api_token' => 'stock-exit-test-token',
        ]);
        $product = Product::create(['name' => 'Stock exit product', 'stock' => 8]);

        $this->postJson('/api/products/' . $product->id . '/stock-movements', [
            'type' => 'salida',
            'quantity' => 3,
            'reason' => 'Order fulfillment',
        ], ['Authorization' => 'Bearer ' . $user->api_token])
            ->assertOk()
            ->assertJsonPath('data.product.stock', 5)
            ->assertJsonPath('data.movement.type', 'salida');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'entity_type' => 'stock_movement']);
    }
}
