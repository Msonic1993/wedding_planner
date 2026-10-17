<?php

namespace App\Application\Chat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Absolute unread counters for one user. Absolute values (not deltas) keep every
 * device of that user consistent even if an event is missed or arrives late.
 */
final class ChatUnreadUpdated implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use InteractsWithSockets;

    public string $broadcastQueue = 'broadcasts';

    public function __construct(
        public readonly int $userId,
        public readonly int $inquiryId,
        public readonly int $unreadCount,
        public readonly int $total,
    ) {}

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("chat.user.{$this->userId}")];
    }

    public function broadcastAs(): string
    {
        return 'chat.unread.updated';
    }

    /** @return array{total: int, inquiry_id: int, unread_count: int} */
    public function broadcastWith(): array
    {
        return [
            'total' => $this->total,
            'inquiry_id' => $this->inquiryId,
            'unread_count' => $this->unreadCount,
        ];
    }
}
