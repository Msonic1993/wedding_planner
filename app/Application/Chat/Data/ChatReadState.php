<?php

namespace App\Application\Chat\Data;

final readonly class ChatReadState
{
    public function __construct(
        public int $inquiryId,
        public int $unreadCount,
        public int $total,
    ) {}
}
