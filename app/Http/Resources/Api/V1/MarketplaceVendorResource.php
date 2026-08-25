<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MarketplaceVendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'description' => $this->description, 'website' => $this->website, 'voivodeship' => $this->voivodeship, 'cities' => $this->cities, 'categories' => $this->categories, 'price_from' => $this->price_from, 'price_to' => $this->price_to, 'available_from' => $this->available_from?->toDateString(), 'available_to' => $this->available_to?->toDateString(), 'rating' => $this->reviews_avg_rating === null ? null : round((float) $this->reviews_avg_rating, 2), 'reviews_count' => (int) ($this->reviews_count ?? 0), 'image_paths' => $this->image_paths];
    }
}
