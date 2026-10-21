<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class VendorEarningFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 5000, 100000);
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
