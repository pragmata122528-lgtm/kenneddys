<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'shop_name' => fake()->company(),
            'shop_description' => fake()->paragraph(),
            'is_approved' => false,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Vendor $vendor): void {
            if ($vendor->user && $vendor->user->role !== UserRole::Vendor->value) {
                $vendor->user->updateQuietly(['role' => UserRole::Vendor->value]);
            }
        });
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => true,
        ]);
    }
}
