<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorEarning;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_automatically_generates_slug_from_name(): void
    {
        $vendor = Vendor::factory()->approved()->create();

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Awesome Gaming Laptop 2026',
            'price' => 1499.99,
            'stock_quantity' => 15,
        ]);

        $this->assertEquals('awesome-gaming-laptop-2026', $product->slug);
    }

    public function test_product_generates_unique_slug_for_duplicate_names(): void
    {
        $vendor = Vendor::factory()->approved()->create();

        $product1 = Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Wireless Mechanical Keyboard',
            'price' => 99.00,
            'stock_quantity' => 20,
        ]);

        $product2 = Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Wireless Mechanical Keyboard',
            'price' => 109.00,
            'stock_quantity' => 10,
        ]);

        $this->assertEquals('wireless-mechanical-keyboard', $product1->slug);
        $this->assertEquals('wireless-mechanical-keyboard-1', $product2->slug);
    }

    public function test_mutator_cleans_manually_provided_slug(): void
    {
        $vendor = Vendor::factory()->approved()->create();

        $product = Product::create([
            'vendor_id' => $vendor->id,
            'name' => 'Custom Mug',
            'slug' => 'Custom Mug Special Edition #1',
            'price' => 19.99,
            'stock_quantity' => 50,
        ]);

        $this->assertEquals('custom-mug-special-edition-1', $product->slug);
    }

    public function test_model_relationships_work_correctly(): void
    {
        $customer = User::factory()->create();
        $vendorUser = User::factory()->vendor()->create();
        $vendor = $vendorUser->vendor;
        $vendor->update(['is_approved' => true]);

        $product = Product::factory()->create([
            'vendor_id' => $vendor->id,
            'price' => 100.00,
            'stock_quantity' => 25,
        ]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'total_amount' => 200.00,
        ]);

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 100.00,
        ]);

        $earning = VendorEarning::factory()->create([
            'vendor_id' => $vendor->id,
            'order_id' => $order->id,
            'order_amount' => 200.00,
            'commission_fee' => 20.00,
            'vendor_earning' => 180.00,
        ]);

        // Assert relationships
        $this->assertEquals($vendor->id, $product->vendor->id);
        $this->assertEquals($customer->id, $order->user->id);
        $this->assertCount(1, $order->items);
        $this->assertEquals($product->id, $order->items->first()->product->id);
        $this->assertEquals(200.00, $orderItem->subtotal);
        $this->assertCount(1, $vendor->earnings);
        $this->assertEquals(180.00, $vendor->earnings->first()->vendor_earning);
    }
}
