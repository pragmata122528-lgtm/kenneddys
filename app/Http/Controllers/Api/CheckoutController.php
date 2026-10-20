<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\CheckoutRequest;
use App\Http\Resources\OrderResource;
use App\Models\User;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    /**
     * Inject CheckoutService via constructor.
     */
    public function __construct(protected CheckoutService $checkoutService) {}

    /**
     * Process checkout transaction for the authenticated buyer.
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $validated = $request->validated();

        $order = $this->checkoutService->execute(
            user: $user,
            itemsData: $validated['items'],
            paymentMethod: $validated['payment_method'] ?? 'stripe',
            shippingAmount: (float) ($validated['shipping_amount'] ?? 0.00),
        );

        return response()->json([
            'message' => 'Order placed successfully.',
            'order' => new OrderResource($order),
        ], 201);
    }
}
