<?php

namespace App\Services\Checkout;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\PaymentFailedException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Payment\PaymentGatewayFactory;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    /**
     * Inject payment gateway factory via constructor (Strategy + Factory patterns).
     */
    public function __construct(protected PaymentGatewayFactory $paymentGatewayFactory) {}

    /**
     * Execute sequential checkout inside a strict database transaction.
     *
     * @param  array<int, array{product_id: int, quantity: int}>  $itemsData
     *
     * @throws InsufficientStockException
     * @throws PaymentFailedException
     */
    public function execute(User $user, array $itemsData, string $paymentMethod = 'stripe', float $shippingAmount = 0.00): Order
    {
        return DB::transaction(function () use ($user, $itemsData, $paymentMethod, $shippingAmount): Order {
            $productIds = array_column($itemsData, 'product_id');

            // Lock products for update to prevent concurrent race conditions
            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $orderItems = [];
            $subtotal = 0.00;

            // Step 1: Verify available inventory sequentially
            foreach ($itemsData as $item) {
                $productId = $item['product_id'];
                $requestedQty = (int) $item['quantity'];

                /** @var Product|null $product */
                $product = $products->get($productId);

                if (! $product || ! $product->is_active) {
                    throw new InsufficientStockException(
                        "Product #{$productId} is either discontinued or unavailable.",
                        ['product_id' => $productId, 'requested_quantity' => $requestedQty, 'available_stock' => 0]
                    );
                }

                if ($product->stock_quantity < $requestedQty) {
                    throw new InsufficientStockException(
                        "Insufficient stock for '{$product->name}'. Requested {$requestedQty}, only {$product->stock_quantity} available.",
                        [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'requested_quantity' => $requestedQty,
                            'available_stock' => $product->stock_quantity,
                        ]
                    );
                }

                $itemPrice = (float) $product->price;
                $lineSubtotal = round($itemPrice * $requestedQty, 2);
                $subtotal += $lineSubtotal;

                $orderItems[] = [
                    'product' => $product,
                    'product_id' => $product->id,
                    'quantity' => $requestedQty,
                    'price' => $itemPrice,
                ];
            }

            // Step 2: Deduct purchased quantities directly from product stock
            foreach ($orderItems as $item) {
                /** @var Product $product */
                $product = $item['product'];
                $product->decrement('stock_quantity', $item['quantity']);
                $product->increment('total_sold', $item['quantity']);
            }

            $totalAmount = round($subtotal + $shippingAmount, 2);

            // Step 3: Generate records in orders and order_items tables
            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'status' => OrderStatus::Processing->value,
                'payment_method' => $paymentMethod,
                'payment_status' => PaymentStatus::Pending->value,
                'shipping_amount' => $shippingAmount,
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }

            // Step 4: Process payment through decoupled strategy resolved by factory
            $gateway = $this->paymentGatewayFactory->make($paymentMethod);
            $amountInCents = (int) round($totalAmount * 100);
            $charged = $gateway->charge($amountInCents, 'usd');

            if (! $charged) {
                throw new PaymentFailedException('Payment authorization was declined. Transaction rolled back.');
            }

            $order->update([
                'payment_status' => $paymentMethod === PaymentMethod::Cod->value
                    ? PaymentStatus::Pending->value
                    : PaymentStatus::Paid->value,
            ]);

            // Step 5: Dispatch event for background queued processing
            OrderPlaced::dispatch($order);

            return $order->load(['items.product.vendor', 'user']);
        });
    }
}
