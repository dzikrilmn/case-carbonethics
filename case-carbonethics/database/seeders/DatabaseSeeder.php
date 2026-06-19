<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test users (use firstOrCreate to avoid duplicates)
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'role' => User::ROLE_USER,
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'),
                'role' => User::ROLE_ADMIN,
            ]
        );

        // Create additional test users only if count is low
        if (User::count() < 7) {
            User::factory(5)->create();
        }

        // Create dummy products only if none exist
        if (Product::count() === 0) {
            $products = Product::factory(10)->create();
        } else {
            $products = Product::all();
        }

        // Create dummy orders only if none exist
        if (Order::count() === 0) {
            Order::factory(5)->create()->each(function (Order $order) use ($products) {
                $selectedProducts = $products->random(rand(2, 4));

                $totalPrice = 0;
                foreach ($selectedProducts as $product) {
                    $qty = rand(1, 5);
                    $price = $product->price;
                    $subtotal = $qty * $price;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'price' => $price,
                        'subtotal' => $subtotal,
                    ]);

                    $totalPrice += $subtotal;
                }

                // Update order total price
                $order->update(['total_price' => $totalPrice]);
            });
        }
    }
}
