<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Favorite\Models\VendorFavorite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VendorFavorite */
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
