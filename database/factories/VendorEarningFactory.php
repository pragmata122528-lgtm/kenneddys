<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Vendor;
use App\Models\VendorEarning;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VendorEarning>
 */
class VendorEarningFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 50, 500);
        $commission = round($amount * 0.10, 2);
        $earning = round($amount - $commission, 2);

        return [
            'vendor_id' => Vendor::factory()->approved(),
            'order_id' => Order::factory(),
            'order_amount' => $amount,
            'commission_fee' => $commission,
            'vendor_earning' => $earning,
        ];
    }
}
