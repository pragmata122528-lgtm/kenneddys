<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Events\OrderPlaced;
use App\Listeners\ProcessOrderFulfillment;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MarketplacePlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_registration_automatically_creates_unapproved_vendor_profile(): void
    {
        $response = $this->postJson(route('auth.register'), [
            'username' => 'techstore',
            'email' => 'tech@example.com',
            'password' => 'SecurePass123!',
            'role' => UserRole::Vendor->value,
            'shop_name' => 'Tech World Gadgets',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.role', 'vendor')
            ->assertJsonPath('user.vendor.is_approved', false)
            ->assertJsonPath('user.vendor.shop_name', 'Tech World Gadgets');

        $user = User::where('email', 'tech@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->vendor);
        $this->assertFalse($user->vendor->is_approved);
    }

    public function test_customer_registration_does_not_create_vendor_profile(): void
    {
        $response = $this->postJson(route('auth.register'), [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'SecurePass123!',
            'role' => UserRole::Customer->value,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.role', 'customer');

        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->vendor);
    }

    public function test_unapproved_vendor_is_blocked_by_middleware_with_403(): void
    {
        $vendorUser = User::factory()->vendor()->create();
        $token = auth('api')->login($vendorUser);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('vendor.products.store'), [
                'name' => 'Pro Headset',
                'price' => 7900.00,
                'stock_quantity' => 20,
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', 'VENDOR_NOT_APPROVED')
            ->assertJsonFragment([
                'message' => 'Your vendor account is pending approval. You cannot manage products until approved.',
            ]);
    }

    public function test_approved_vendor_can_create_product_with_auto_generated_slug(): void
    {
        $vendorUser = User::factory()->vendor()->create();
        $vendor = $vendorUser->vendor;
        $vendor->update(['is_approved' => true]);

        $token = auth('api')->login($vendorUser);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('vendor.products.store'), [
                'name' => 'Wireless Bluetooth Earbuds Pro',
                'price' => 8500.00,
                'stock_quantity' => 45,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('product.slug', 'wireless-bluetooth-earbuds-pro')
            ->assertJsonPath('product.vendor_id', $vendor->id)
            ->assertJsonPath('product.stock_quantity', 45);

        $this->assertDatabaseHas('products', [
            'slug' => 'wireless-bluetooth-earbuds-pro',
            'vendor_id' => $vendor->id,
        ]);
    }

    public function test_checkout_executes_sequential_transaction_and_deducts_inventory(): void
    {
        Event::fake([OrderPlaced::class]);

        $buyer = User::factory()->create();
        $vendorUser = User::factory()->vendor()->create();
        $vendor = $vendorUser->vendor;
        $vendor->update(['is_approved' => true]);

        $product = Product::factory()->create([
            'vendor_id' => $vendor->id,
            'price' => 5000.00,
            'stock_quantity' => 10,
        ]);

        $token = auth('api')->login($buyer);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('checkout'), [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3],
                ],
                'payment_method' => 'jazzcash',
                'shipping_amount' => 250.00,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('order.total_amount', fn ($amount) => (float) $amount === 15250.0)
            ->assertJsonPath('order.payment_status', PaymentStatus::Paid->value)
            ->assertJsonPath('order.status', OrderStatus::Processing->value);

        $this->assertEquals(7, $product->fresh()->stock_quantity);
        $this->assertEquals(3, $product->fresh()->total_sold);

        $this->assertDatabaseHas('orders', [
            'user_id' => $buyer->id,
            'total_amount' => 15250.00,
            'payment_method' => 'jazzcash',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'quantity' => 3,
            'price' => 5000.00,
        ]);

        Event::assertDispatched(OrderPlaced::class);
    }

    public function test_checkout_rollback_when_requested_quantity_exceeds_available_stock(): void
    {
        $buyer = User::factory()->create();
        $vendorUser = User::factory()->vendor()->create();
        $vendor = $vendorUser->vendor;
        $vendor->update(['is_approved' => true]);

        $product1 = Product::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Product In Stock',
            'stock_quantity' => 10,
        ]);

        $product2 = Product::factory()->create([
            'vendor_id' => $vendor->id,
            'name' => 'Product Low Stock',
            'stock_quantity' => 2,
        ]);

        $token = auth('api')->login($buyer);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('checkout'), [
                'items' => [
                    ['product_id' => $product1->id, 'quantity' => 2],
                    ['product_id' => $product2->id, 'quantity' => 5],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'INSUFFICIENT_STOCK');

        $this->assertEquals(10, $product1->fresh()->stock_quantity);
        $this->assertEquals(2, $product2->fresh()->stock_quantity);

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_payment_gateway_interface_can_be_mocked_and_fails_safely(): void
    {
        $this->mock(PaymentGatewayInterface::class, function ($mock): void {
            $mock->shouldReceive('charge')
                ->once()
                ->andReturn(false);
        });

        $buyer = User::factory()->create();
        $vendorUser = User::factory()->vendor()->create();
        $vendor = $vendorUser->vendor;
        $vendor->update(['is_approved' => true]);

        $product = Product::factory()->create([
            'vendor_id' => $vendor->id,
            'price' => 2500.00,
            'stock_quantity' => 5,
        ]);

        $token = auth('api')->login($buyer);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('checkout'), [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => 'jazzcash',
            ]);

        $response->assertStatus(402)
            ->assertJsonPath('code', 'PAYMENT_FAILED');

        $this->assertEquals(5, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_fulfillment_listener_calculates_ten_percent_platform_commission(): void
    {
        $buyer = User::factory()->create();
        $vendorUser = User::factory()->vendor()->create();
        $vendor = $vendorUser->vendor;
        $vendor->update(['is_approved' => true]);

        $product = Product::factory()->create([
            'vendor_id' => $vendor->id,
            'price' => 20000.00,
            'stock_quantity' => 10,
        ]);

        $token = auth('api')->login($buyer);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson(route('checkout'), [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => 'easypaisa',
            ]);

        $response->assertStatus(201);
        $orderId = $response->json('order.id');

        $order = Order::with(['user', 'items.product.vendor'])->find($orderId);
        $listener = new ProcessOrderFulfillment;
        $listener->handle(new OrderPlaced($order));

        $this->assertDatabaseHas('vendor_earnings', [
            'vendor_id' => $vendor->id,
            'order_id' => $orderId,
            'order_amount' => 20000.00,
            'commission_fee' => 2000.00,
            'vendor_earning' => 18000.00,
        ]);
    }

    public function test_admin_can_approve_vendor(): void
    {
        $admin = User::factory()->admin()->create();
        $vendorUser = User::factory()->vendor()->create();
        $vendor = $vendorUser->vendor;

        $this->assertFalse($vendor->is_approved);

        $token = auth('api')->login($admin);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->patchJson(route('admin.vendors.approve', $vendor));

        $response->assertStatus(200)
            ->assertJsonPath('vendor.is_approved', true);

        $this->assertTrue($vendor->fresh()->is_approved);
    }
}
