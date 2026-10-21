<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateVendorProfileRequest;
use App\Http\Resources\OrderItemResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\VendorEarningResource;
use App\Http\Resources\VendorResource;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VendorController extends Controller
{
    public function profile(): JsonResponse
    {
        $user = auth('api')->user();
        $vendor = $user->vendor?->loadCount('products');

        if (! $vendor) {
            return response()->json([
                'message' => 'Vendor profile not found.',
                'code' => 'VENDOR_NOT_FOUND',
            ], 404);
        }

        $totalEarnings = (float) $vendor->earnings()->sum('vendor_earning');

        return response()->json([
            'vendor' => new VendorResource($vendor),
            'total_earnings' => $totalEarnings,
        ]);
    }

    public function updateProfile(UpdateVendorProfileRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        $vendor = $user->vendor;

        if (! $vendor) {
            return response()->json([
                'message' => 'Vendor profile not found.',
                'code' => 'VENDOR_NOT_FOUND',
            ], 404);
        }

        $vendor->update($request->validated());

        return response()->json([
            'message' => 'Vendor profile updated successfully.',
            'vendor' => new VendorResource($vendor->fresh()),
        ]);
    }

    public function products(Request $request): AnonymousResourceCollection
    {
        $user = auth('api')->user();
        $vendor = $user->vendor;

        $products = $vendor->products()
            ->latest('id')
            ->paginate((int) $request->query('per_page', 15));

        return ProductResource::collection($products);
    }

    public function orders(Request $request): AnonymousResourceCollection
    {
        $user = auth('api')->user();
        $vendor = $user->vendor;

        $orderItems = OrderItem::query()
            ->whereHas('product', fn ($query) => $query->where('vendor_id', $vendor->id))
            ->with(['order.user', 'product'])
            ->latest('id')
            ->paginate((int) $request->query('per_page', 15));

        return OrderItemResource::collection($orderItems);
    }

    public function earnings(Request $request): AnonymousResourceCollection
    {
        $user = auth('api')->user();
        $vendor = $user->vendor;

        $earnings = $vendor->earnings()
            ->with('order')
            ->latest('id')
            ->paginate((int) $request->query('per_page', 15));

        return VendorEarningResource::collection($earnings);
    }
}
