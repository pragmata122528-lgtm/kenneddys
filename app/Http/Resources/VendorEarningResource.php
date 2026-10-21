<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorEarningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'order_id' => $this->order_id,
            'order_amount' => (float) $this->order_amount,
            'commission_fee' => (float) $this->commission_fee,
            'vendor_earning' => (float) $this->vendor_earning,
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'order' => new OrderResource($this->whenLoaded('order')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
