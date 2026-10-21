<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'price' => (float) $this->price,
            'stock_quantity' => (int) $this->stock_quantity,
            'total_sold' => (int) $this->total_sold,
            'is_active' => (bool) $this->is_active,
            'flat_discount' => $this->flat_discount !== null ? (float) $this->flat_discount : null,
            'percentage_discount' => $this->percentage_discount !== null ? (int) $this->percentage_discount : null,
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
