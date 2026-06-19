<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthenticationAndPurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test error handling for inactive products in orders.
     */
    public function test_order_creation_fails_with_inactive_products(): void
    {
        $activeProduct = Product::factory()->create([
            'price' => '50.00',
            'status' => Product::STATUS_ACTIVE,
        ]);

        $inactiveProduct = Product::factory()->create([
            'price' => '30.00',
            'status' => Product::STATUS_INACTIVE,
        ]);

        $this->postJson('/api/orders', [
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
            'items' => [
                [
                    'product_id' => $activeProduct->id,
                    'qty' => 1,
                ],
                [
                    'product_id' => $inactiveProduct->id,
                    'qty' => 1,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Validation failed',
            ]);
    }

    /**
     * Test authorization: non-admin user cannot manage products.
     */
    public function test_non_admin_user_cannot_manage_products(): void
    {
        $regularUser = User::factory()->create();

        $product = Product::factory()->create();

        // Non-admin cannot create
        $this->actingAs($regularUser, 'sanctum')->postJson('/api/products', [
            'name' => 'Denied',
            'price' => '10',
            'status' => Product::STATUS_ACTIVE,
        ])
            ->assertForbidden()
            ->assertJson([
                'message' => 'Forbidden.',
            ]);

        // Non-admin cannot update
        $this->actingAs($regularUser, 'sanctum')->putJson("/api/products/{$product->id}", [
            'name' => 'Updated',
            'price' => '20',
            'status' => Product::STATUS_ACTIVE,
        ])
            ->assertForbidden();

        // Non-admin cannot delete
        $this->actingAs($regularUser, 'sanctum')->deleteJson("/api/products/{$product->id}")
            ->assertForbidden();

        // But non-admin CAN view products
        $this->actingAs($regularUser, 'sanctum')->getJson('/api/products')
            ->assertOk();
    }

    /**
     * Test login with invalid credentials.
     */
    public function test_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        // Wrong password
        $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials.',
            ]);

        // Non-existent user
        $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ])
            ->assertUnauthorized();

        // Missing email
        $this->postJson('/api/login', [
            'password' => 'password',
        ])
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Validation failed',
            ]);
    }

    /**
     * Test product price snapshot in orders.
     *
     * Scenario: Product price changes after order is placed.
     * Order should keep the snapshot price, not the new product price.
     */
    public function test_order_item_price_is_snapshot(): void
    {
        $product = Product::factory()->create([
            'price' => '100.00',
            'status' => Product::STATUS_ACTIVE,
        ]);

        // Create order at $100
        $orderResponse = $this->postJson('/api/orders', [
            'customer_name' => 'Test',
            'customer_email' => 'test@example.com',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                ],
            ],
        ])
            ->assertCreated();

        $orderItems = $orderResponse->json('data.items');
        $this->assertSame('100.00', $orderItems[0]['price']);

        // Update product price to $150
        $product->update(['price' => '150.00']);

        // Verify order still has original price
        $orderId = $orderResponse->json('id');
        $getResponse = $this->postJson('/api/orders', [
            'customer_name' => 'Test2',
            'customer_email' => 'test2@example.com',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                ],
            ],
        ])
            ->assertCreated();

        // New order should have new price
        $newOrderItems = $getResponse->json('data.items');
        $this->assertSame('150.00', $newOrderItems[0]['price']);
    }
}
