<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(protected CheckoutService $checkoutService) {}

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $validated = $request->validated();

        $order = $this->checkoutService->execute(
            user: $user,
            itemsData: $validated['items'],
            paymentMethod: $validated['payment_method'] ?? 'jazzcash',
            shippingAmount: (float) ($validated['shipping_amount'] ?? 0.00),
            simulateFailure: (bool) ($validated['simulate_failure'] ?? false),
        );

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => new OrderResource($order),
        ], 201);
    }
}
