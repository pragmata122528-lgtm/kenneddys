<?php

namespace Database\Factories;

use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'vendor_id' => Vendor::factory()->approved(),
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'image' => null,
            'price' => fake()->randomFloat(2, 500, 50000), // PKR
            'stock_quantity' => fake()->numberBetween(10, 100),
            'total_sold' => 0,
            'is_active' => true,
            'sort_order' => 0,
            'flat_discount' => null,
            'percentage_discount' => null,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
