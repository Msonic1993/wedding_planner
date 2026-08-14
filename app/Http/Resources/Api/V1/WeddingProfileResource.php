<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Profile\Models\WeddingProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin WeddingProfile */
class WeddingProfileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $disk = (string) config('filesystems.profile_photo_disk', 'public');

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'wedding_date' => $this->wedding_date?->toDateString(),
            'budget' => $this->budget,
            'guest_count' => $this->guest_count,
            'location' => $this->location,
            'photo_url' => $this->photo_path === null ? null : Storage::disk($disk)->url($this->photo_path),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
