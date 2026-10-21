<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorEarning;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedVendorEarnings extends Command
{
    protected $signature = 'app:seed-vendor-earnings';

    protected $description = 'Seed sample orders and vendor earnings for testing';

    public function handle(): int
    {
        $vendor = Vendor::where('shop_name', 'like', '%Khaadi%')->first();
        if (! $vendor) {
            $this->error('Khaadi vendor not found.');

            return self::FAILURE;
        }

        $customer = User::where('email', 'customer@gmail.com')->first();
        if (! $customer) {
            $this->error('Customer user not found.');

            return self::FAILURE;
        }

        $products = Product::where('vendor_id', $vendor->id)->where('is_active', true)->take(3)->get();
        if ($products->isEmpty()) {
            $this->error('No active products found for vendor.');

            return self::FAILURE;
        }

        $this->info("Vendor: {$vendor->shop_name} (ID: {$vendor->id})");
        $this->info("Customer: {$customer->email} (ID: {$customer->id})");
        $this->info("Products: {$products->pluck('name')->join(', ')}");

        $orderData = [
            ['status' => 'delivered', 'payment_status' => 'paid', 'days_ago' => 14],
            ['status' => 'delivered', 'payment_status' => 'paid', 'days_ago' => 7],
            ['status' => 'processing', 'payment_status' => 'paid', 'days_ago' => 2],
        ];

        foreach ($orderData as $i => $meta) {
            $product = $products[$i % $products->count()];
            $qty = rand(1, 3);
            $price = (float) $product->price;
            $subtotal = round($price * $qty, 2);
            $shipping = round(rand(150, 350), 2);
            $total = round($subtotal + $shipping, 2);

            $order = Order::create([
                'user_id' => $customer->id,
                'total_amount' => $total,
                'status' => $meta['status'],
                'payment_method' => 'jazzcash',
                'payment_status' => $meta['payment_status'],
                'shipping_amount' => $shipping,
            ]);

            $backdatedAt = now()->subDays($meta['days_ago']);
            DB::table('orders')->where('id', $order->id)->update(['created_at' => $backdatedAt, 'updated_at' => $backdatedAt]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $qty,
                'price' => $price,
            ]);

            $commissionFee = round($subtotal * 0.10, 2);
            $vendorPayout = round($subtotal - $commissionFee, 2);

            VendorEarning::create([
                'vendor_id' => $vendor->id,
                'order_id' => $order->id,
                'order_amount' => $subtotal,
                'commission_fee' => $commissionFee,
                'vendor_earning' => $vendorPayout,
            ]);

            DB::table('vendor_earnings')->where('order_id', $order->id)->update(['created_at' => $backdatedAt, 'updated_at' => $backdatedAt]);

            $this->info("Order #{$order->order_number}: {$product->name} x{$qty} = PKR {$total} (Vendor earns: PKR {$vendorPayout})");
        }

        $totalEarnings = VendorEarning::where('vendor_id', $vendor->id)->sum('vendor_earning');
        $this->info("Total vendor earnings: PKR {$totalEarnings}");

        return self::SUCCESS;
    }
}
