<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 10, 500);

        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'price' => $price,
            'status' => Product::STATUS_ACTIVE,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Product::STATUS_ACTIVE,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Product::STATUS_INACTIVE,
        ]);
    }
}
