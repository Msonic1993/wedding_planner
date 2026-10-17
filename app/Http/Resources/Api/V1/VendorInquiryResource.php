<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Chat\Models\VendorInquiry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin VendorInquiry */
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
            'messages_count' => $this->messages_count,
            'last_message_at' => $this->last_message_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
