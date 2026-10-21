<?php

namespace Tests\Feature;

use App\Events\OrderPlaced;
use App\Listeners\ProcessOrderFulfillment;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Payment\CodPaymentGateway;
use App\Services\Payment\EasypaisaPaymentGateway;
use App\Services\Payment\JazzCashPaymentGateway;
use App\Services\Payment\PaymentGatewayFactory;
use App\Services\Payment\PaymentGatewayInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class ArchitectureAndQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_gateway_factory_resolves_strategy_implementations(): void
    {
        $factory = new PaymentGatewayFactory;

        $jazzcash = $factory->make('jazzcash');
        $this->assertInstanceOf(PaymentGatewayInterface::class, $jazzcash);
        $this->assertInstanceOf(JazzCashPaymentGateway::class, $jazzcash);

        $easypaisa = $factory->make('easypaisa');
        $this->assertInstanceOf(PaymentGatewayInterface::class, $easypaisa);
        $this->assertInstanceOf(EasypaisaPaymentGateway::class, $easypaisa);

        $cod = $factory->make('cod');
        $this->assertInstanceOf(PaymentGatewayInterface::class, $cod);
        $this->assertInstanceOf(CodPaymentGateway::class, $cod);
    }

    public function test_payment_gateway_factory_throws_exception_on_unknown_driver(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $factory = new PaymentGatewayFactory;
        $factory->make('cryptocurrency');
    }

    public function test_model_local_scopes_isolate_records_correctly(): void
    {
        $vendorUser1 = User::factory()->vendor()->create();
        $vendor1 = $vendorUser1->vendor;
        $vendor1->update(['is_approved' => true]);

        $vendorUser2 = User::factory()->vendor()->create();
        $vendor2 = $vendorUser2->vendor;

        $this->assertTrue(Vendor::approved()->where('id', $vendor1->id)->exists());
        $this->assertFalse(Vendor::approved()->where('id', $vendor2->id)->exists());
        $this->assertTrue(Vendor::pending()->where('id', $vendor2->id)->exists());

        $activeProduct = Product::factory()->create([
            'vendor_id' => $vendor1->id,
            'is_active' => true,
            'stock_quantity' => 10,
        ]);

        $inactiveProduct = Product::factory()->create([
            'vendor_id' => $vendor1->id,
            'is_active' => false,
            'stock_quantity' => 10,
        ]);

        $outOfStockProduct = Product::factory()->create([
            'vendor_id' => $vendor1->id,
            'is_active' => true,
            'stock_quantity' => 0,
        ]);

        $this->assertTrue(Product::active()->where('id', $activeProduct->id)->exists());
        $this->assertFalse(Product::active()->where('id', $inactiveProduct->id)->exists());
        $this->assertTrue(Product::inStock()->where('id', $activeProduct->id)->exists());
        $this->assertFalse(Product::inStock()->where('id', $outOfStockProduct->id)->exists());
        $this->assertTrue(Product::forVendor($vendor1->id)->where('id', $activeProduct->id)->exists());
    }

    public function test_n_plus_one_prevention_is_strictly_enforced(): void
    {
        $customer = User::factory()->create();
        $vendorUser = User::factory()->vendor()->create();
        $vendor = $vendorUser->vendor;
        $vendor->update(['is_approved' => true]);

        Product::factory()->create(['vendor_id' => $vendor->id]);

        $token = auth('api')->login($customer);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson(route('products.index'));

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name', 'vendor' => ['id', 'shop_name']]]]);
    }

    public function test_redis_queue_processes_background_fulfillment_job(): void
    {
        Config::set('queue.default', 'redis');

        $customer = User::factory()->create();
        $vendorUser = User::factory()->vendor()->create();
        $vendor = $vendorUser->vendor;
        $vendor->update(['is_approved' => true]);

        $product = Product::factory()->create([
            'vendor_id' => $vendor->id,
            'price' => 3000.00,
            'stock_quantity' => 20,
        ]);

        $order = Order::factory()->create([
            'user_id' => $customer->id,
            'total_amount' => 3000.00,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 3000.00,
        ]);

        $listener = new ProcessOrderFulfillment;
        dispatch(function () use ($listener, $order): void {
            $listener->handle(new OrderPlaced($order));
        })->onConnection('redis');

        $queueLength = (int) Redis::connection()->llen('queues:default');
        $this->assertGreaterThan(0, $queueLength);

        Artisan::call('queue:work', [
            'connection' => 'redis',
            '--once' => true,
        ]);

        $this->assertDatabaseHas('vendor_earnings', [
            'vendor_id' => $vendor->id,
            'order_id' => $order->id,
            'order_amount' => 3000.00,
            'commission_fee' => 300.00,
            'vendor_earning' => 2700.00,
        ]);
    }
}
