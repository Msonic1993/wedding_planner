<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorInquiryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor' => [
                'id' => $this->vendorProfile->id,
                'name' => $this->vendorProfile->name,
            ],
            'couple' => [
                'id' => $this->couple->id,
                'name' => $this->couple->name,
            ],
            'requested_date' => $this->requested_date?->toDateString(),
            'messages_count' => isset($this->messages_count) ? (int) $this->messages_count : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
