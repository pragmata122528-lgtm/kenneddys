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
    public function __construct(protected PaymentGatewayFactory $paymentGatewayFactory) {}

    public function execute(
        User $user,
        array $itemsData,
        string $paymentMethod = 'jazzcash',
        float $shippingAmount = 0.00,
        bool $simulateFailure = false
    ): Order {
        return DB::transaction(function () use ($user, $itemsData, $paymentMethod, $shippingAmount, $simulateFailure): Order {
            $productIds = array_column($itemsData, 'product_id');

            $products = Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $orderItems = [];
            $subtotal = 0.00;

            foreach ($itemsData as $item) {
                $productId = $item['product_id'];
                $requestedQty = (int) $item['quantity'];
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

            foreach ($orderItems as $item) {
                $product = $item['product'];
                $product->decrement('stock_quantity', $item['quantity']);
                $product->increment('total_sold', $item['quantity']);
            }

            $totalAmount = round($subtotal + $shippingAmount, 2);

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

            $gateway = $this->paymentGatewayFactory->make($paymentMethod);
            $charged = ! $simulateFailure && $gateway->charge((int) round($totalAmount), 'PKR');

            if (! $charged) {
                throw new PaymentFailedException('Payment authorization was declined. Transaction and inventory hold have been rolled back.');
            }

            $order->update([
                'payment_status' => $paymentMethod === PaymentMethod::Cod->value
                    ? PaymentStatus::Pending->value
                    : PaymentStatus::Paid->value,
            ]);

            OrderPlaced::dispatch($order);

            return $order->load(['items.product.vendor', 'user']);
        });
    }
}
