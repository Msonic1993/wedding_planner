<?php

namespace App\Application\Chat\Data;

use App\Domain\Chat\Models\ChatMessage;
use Illuminate\Support\Collection;

readonly class ChatMessageWindow
{
    /** @param Collection<int, ChatMessage> $messages Sorted oldest first. */
    public function __construct(
        public Collection $messages,
        public bool $hasMore,
    ) {}
}
