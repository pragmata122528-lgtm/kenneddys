<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'shop_name' => $this->shop_name,
            'shop_description' => $this->shop_description,
            'is_approved' => (bool) $this->is_approved,
            'user' => new UserResource($this->whenLoaded('user')),
            'products_count' => $this->whenCounted('products'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
