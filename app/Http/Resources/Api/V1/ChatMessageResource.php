<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'sender' => [
                'id' => $this->sender->id,
                'name' => $this->sender->name,
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
