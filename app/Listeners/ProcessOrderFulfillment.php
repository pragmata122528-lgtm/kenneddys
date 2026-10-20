<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\VendorEarning;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessOrderFulfillment implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order->loadMissing(['user', 'items.product.vendor']);

        // 1. Send simulated email receipt to the customer
        if ($order->user && ! empty($order->user->email)) {
            Log::info("Order confirmation email sent to {$order->user->email} for Order #{$order->order_number}", [
                'order_id' => $order->id,
                'total_amount' => $order->total_amount,
            ]);
        }

        // 2. Automatically calculate and log flat 10% platform commission fee into vendor_earnings
        $vendorGroups = $order->items->groupBy(fn ($item) => $item->product->vendor_id);

        foreach ($vendorGroups as $vendorId => $items) {
            if (! $vendorId) {
                continue;
            }

            $vendorTotal = (float) $items->sum(function ($item): float {
                return (float) $item->price * $item->quantity;
            });

            $commissionFee = round($vendorTotal * 0.10, 2);
            $vendorPayout = round($vendorTotal - $commissionFee, 2);

            VendorEarning::create([
                'vendor_id' => $vendorId,
                'order_id' => $order->id,
                'order_amount' => $vendorTotal,
                'commission_fee' => $commissionFee,
                'vendor_earning' => $vendorPayout,
            ]);

            Log::info("Recorded vendor earnings ledger entry for Vendor #{$vendorId}", [
                'order_id' => $order->id,
                'order_amount' => $vendorTotal,
                'commission_fee' => $commissionFee,
                'vendor_earning' => $vendorPayout,
            ]);
        }
    }
}
