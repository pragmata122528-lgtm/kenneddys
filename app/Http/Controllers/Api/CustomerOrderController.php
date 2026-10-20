<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerOrderController extends Controller
{
    /**
     * Display a paginated listing of the authenticated customer's orders.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = auth('api')->user();

        $orders = $user->orders()
            ->with(['items.product.vendor'])
            ->latest('id')
            ->paginate((int) $request->query('per_page', 15));

        return OrderResource::collection($orders);
    }

    /**
     * Display a specific order belonging to the customer.
     */
    public function show(Order $order): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if ($order->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json([
                'message' => 'Forbidden. You do not have access to this order.',
                'code' => 'ORDER_FORBIDDEN',
            ], 403);
        }

        return response()->json([
            'order' => new OrderResource($order->load(['items.product.vendor', 'user'])),
        ]);
    }
}
