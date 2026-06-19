<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogAndOrderSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_and_order_tables_expose_expected_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('products', [
            'id',
            'name',
            'description',
            'price',
            'status',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('orders', [
            'id',
            'customer_name',
            'customer_email',
            'status',
            'total_price',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('order_items', [
            'id',
            'order_id',
            'product_id',
            'qty',
            'price',
            'subtotal',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_order_item_relations_and_snapshot_totals_work(): void
    {
        $product = Product::factory()->create([
            'price' => '49.99',
        ]);

        $order = Order::factory()->create([
            'total_price' => '99.98',
        ]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'qty' => 2,
            'price' => '49.99',
            'subtotal' => '99.98',
        ]);

        $this->assertSame($order->id, $orderItem->order->id);
        $this->assertSame($product->id, $orderItem->product->id);
        $this->assertSame('99.98', $orderItem->subtotal);
        $this->assertSame(1, $order->items()->count());
        $this->assertSame(1, $product->orderItems()->count());
    }
}
