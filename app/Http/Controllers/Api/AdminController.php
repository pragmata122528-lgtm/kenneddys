<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorEarningResource;
use App\Http\Resources\VendorResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorEarning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminController extends Controller
{
    public function vendors(Request $request): AnonymousResourceCollection
    {
        $vendors = Vendor::query()
            ->with('user')
            ->withCount('products')
            ->when($request->filled('is_approved'), function ($query) use ($request): void {
                $query->where('is_approved', filter_var($request->query('is_approved'), FILTER_VALIDATE_BOOLEAN));
            })
            ->latest('id')
            ->paginate((int) $request->query('per_page', 15));

        return VendorResource::collection($vendors);
    }

    public function approveVendor(Vendor $vendor): JsonResponse
    {
        $vendor->update(['is_approved' => true]);

        return response()->json([
            'message' => "Vendor '{$vendor->shop_name}' has been approved successfully.",
            'vendor' => new VendorResource($vendor->fresh('user')),
        ]);
    }

    public function rejectVendor(Vendor $vendor): JsonResponse
    {
        $vendor->update(['is_approved' => false]);

        return response()->json([
            'message' => "Vendor '{$vendor->shop_name}' approval has been revoked.",
            'vendor' => new VendorResource($vendor->fresh('user')),
        ]);
    }

    public function earningsLedger(Request $request): AnonymousResourceCollection
    {
        $ledger = VendorEarning::query()
            ->with(['vendor.user', 'order'])
            ->latest('id')
            ->paginate((int) $request->query('per_page', 20));

        return VendorEarningResource::collection($ledger);
    }

    public function platformStats(): JsonResponse
    {
        $totalCommission = (float) VendorEarning::sum('commission_fee');
        $totalVendorPayouts = (float) VendorEarning::sum('vendor_earning');
        $totalOrders = Order::count();
        $totalProducts = Product::count();
        $totalVendors = Vendor::count();
        $pendingVendors = Vendor::pending()->count();

        return response()->json([
            'total_commission_collected' => $totalCommission,
            'total_vendor_payouts' => $totalVendorPayouts,
            'total_orders' => $totalOrders,
            'total_products' => $totalProducts,
            'total_vendors' => $totalVendors,
            'pending_vendor_approvals' => $pendingVendors,
        ]);
    }
}
