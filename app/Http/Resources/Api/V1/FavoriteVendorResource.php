<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FavoriteVendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor' => new MarketplaceVendorResource($this->vendorProfile),
            'favorited_at' => $this->created_at?->toISOString(),
        ];
    }
}
