<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Display a paginated listing of active products with eager loaded vendors.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->active()
            ->with('vendor')
            ->when($request->filled('search'), function ($query) use ($request): void {
                $query->where('name', 'like', "%{$request->query('search')}%");
            })
            ->when($request->filled('vendor_id'), function ($query) use ($request): void {
                $query->where('vendor_id', $request->query('vendor_id'));
            })
            ->latest('id')
            ->paginate((int) $request->query('per_page', 15));

        return ProductResource::collection($products);
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product->load('vendor'));
    }

    /**
     * Store a newly created product (authorized for approved vendors).
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();
        $vendor = $user->vendor;

        $product = $vendor->products()->create($request->validated());

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => new ProductResource($product->load('vendor')),
        ], 201);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if ($product->vendor_id !== $user->vendor?->id) {
            return response()->json([
                'message' => 'Forbidden. You do not own this product.',
                'code' => 'NOT_PRODUCT_OWNER',
            ], 403);
        }

        $product->update($request->validated());

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => new ProductResource($product->fresh('vendor')),
        ]);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if ($product->vendor_id !== $user->vendor?->id) {
            return response()->json([
                'message' => 'Forbidden. You do not own this product.',
                'code' => 'NOT_PRODUCT_OWNER',
            ], 403);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}
