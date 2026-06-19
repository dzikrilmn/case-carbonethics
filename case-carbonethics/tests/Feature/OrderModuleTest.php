<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_can_create_order_with_active_products(): void
    {
        $product1 = Product::factory()->create([
            'name' => 'Eco Bottle',
            'price' => 50.00,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $product2 = Product::factory()->create([
            'name' => 'Eco Bag',
            'price' => 30.00,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@mail.com',
            'items' => [
                ['product_id' => $product1->id, 'qty' => 2],
                ['product_id' => $product2->id, 'qty' => 3],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.customer_name', 'Budi')
            ->assertJsonPath('data.customer_email', 'budi@mail.com')
            ->assertJsonPath('data.status', Order::STATUS_PENDING)
            ->assertJsonPath('data.total_price', '190.00')
            ->assertJsonCount(2, 'data.items');

        // Verify order items have price snapshot
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product1->id,
            'qty' => 2,
            'price' => '50.00',
            'subtotal' => '100.00',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product2->id,
            'qty' => 3,
            'price' => '30.00',
            'subtotal' => '90.00',
        ]);
    }

    public function test_cannot_create_order_with_inactive_products(): void
    {
        $activeProduct = Product::factory()->create([
            'price' => 50.00,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $inactiveProduct = Product::factory()->create([
            'price' => 30.00,
            'status' => Product::STATUS_INACTIVE,
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@mail.com',
            'items' => [
                ['product_id' => $activeProduct->id, 'qty' => 1],
                ['product_id' => $inactiveProduct->id, 'qty' => 1],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonFragment(['Product is not active.']);

        $this->assertDatabaseMissing('orders', [
            'customer_email' => 'budi@mail.com',
        ]);
    }

    public function test_cannot_create_order_with_non_existent_product(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@mail.com',
            'items' => [
                ['product_id' => 999, 'qty' => 1],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed');
    }

    public function test_order_requires_valid_email(): void
    {
        $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Budi',
            'customer_email' => 'not-an-email',
            'items' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed');
    }

    public function test_admin_can_list_all_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE]);

        $this->postJson('/api/orders', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@mail.com',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ]);

        $this->postJson('/api/orders', [
            'customer_name' => 'Andi',
            'customer_email' => 'andi@mail.com',
            'items' => [['product_id' => $product->id, 'qty' => 2]],
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/orders');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_view_single_order(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE]);

        $createResponse = $this->postJson('/api/orders', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@mail.com',
            'items' => [['product_id' => $product->id, 'qty' => 2]],
        ]);

        $orderId = $createResponse->json('data.id');

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/orders/' . $orderId);

        $response
            ->assertOk()
            ->assertJsonPath('data.customer_name', 'Budi')
            ->assertJsonCount(1, 'data.items');
    }

    public function test_non_admin_cannot_list_orders(): void
    {
        $user = User::factory()->user()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/orders')
            ->assertForbidden();
    }

    public function test_non_admin_cannot_view_single_order(): void
    {
        $user = User::factory()->user()->create();
        $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE]);

        $createResponse = $this->postJson('/api/orders', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@mail.com',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ]);

        $orderId = $createResponse->json('data.id');

        $this->actingAs($user, 'sanctum')->getJson('/api/orders/' . $orderId)
            ->assertForbidden();
    }

    public function test_order_requires_customer_name_and_email(): void
    {
        $product = Product::factory()->create(['status' => Product::STATUS_ACTIVE]);

        $response = $this->postJson('/api/orders', [
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed');
    }

    public function test_order_requires_at_least_one_item(): void
    {
        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@mail.com',
            'items' => [],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Validation failed');
    }
}
