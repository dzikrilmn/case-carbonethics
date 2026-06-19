<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_endpoints_list_and_show_products(): void
    {
        $firstProduct = Product::factory()->create(['name' => 'First Product']);
        $secondProduct = Product::factory()->create(['name' => 'Second Product']);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonFragment(['name' => $firstProduct->name])
            ->assertJsonFragment(['name' => $secondProduct->name]);

        $this->getJson('/api/products/' . $firstProduct->id)
            ->assertOk()
            ->assertJsonFragment(['name' => $firstProduct->name]);
    }

    public function test_missing_product_returns_standard_404_response(): void
    {
        $this->getJson('/api/products/999999')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Resource not found.',
            ]);
    }

    public function test_admin_can_create_update_and_delete_products(): void
    {
        $admin = User::factory()->admin()->create();

        $createResponse = $this->actingAs($admin, 'sanctum')->postJson('/api/products', [
            'name' => 'Reusable Bottle',
            'description' => 'Stainless steel bottle',
            'price' => 129.50,
            'status' => Product::STATUS_ACTIVE,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonFragment(['name' => 'Reusable Bottle']);

        $productId = $createResponse->json('data.id');

        $this->actingAs($admin, 'sanctum')->putJson('/api/products/' . $productId, [
            'name' => 'Updated Bottle',
            'description' => 'Updated description',
            'price' => 149.75,
            'status' => Product::STATUS_INACTIVE,
        ])
            ->assertOk()
            ->assertJsonFragment(['name' => 'Updated Bottle']);

        $this->actingAs($admin, 'sanctum')->deleteJson('/api/products/' . $productId)
            ->assertOk()
            ->assertJsonFragment([
                'message' => 'Product deleted successfully.',
            ]);

        $this->assertDatabaseMissing('products', [
            'id' => $productId,
        ]);
    }

    public function test_non_admin_cannot_manage_products(): void
    {
        $user = User::factory()->user()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/products', [
            'name' => 'Denied Product',
            'price' => 10,
            'status' => Product::STATUS_ACTIVE,
        ])->assertForbidden();
    }

    public function test_guest_cannot_create_products(): void
    {
        $this->postJson('/api/products', [
            'name' => 'Guest Product',
            'price' => 10,
            'status' => Product::STATUS_ACTIVE,
        ])
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_product_validation_requires_name_price_and_valid_status(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/products', [
            'price' => -1,
            'status' => 'archived',
        ])
            ->assertUnprocessable()
            ->assertJson([
                'message' => 'Validation failed',
            ])
            ->assertJsonValidationErrors(['name', 'price', 'status']);
    }
}
