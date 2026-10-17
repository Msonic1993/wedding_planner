<?php

namespace App\Application\Chat\Events;

use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\VendorInquiry;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

/**
 * Carries a ready-made payload instead of models, so the queue worker
 * broadcasts without touching the database.
 */
final class ChatMessageSent implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use InteractsWithSockets;

    public string $broadcastQueue = 'broadcasts';

    /**
     * @param  list<int>  $participantIds
     * @param  array{id: int, body: string, sender: array{id: int, name: string}, created_at: string|null}  $message
     */
    public function __construct(
        public readonly int $inquiryId,
        public readonly array $participantIds,
        public readonly array $message,
    ) {}

    public static function fromMessage(VendorInquiry $inquiry, ChatMessage $message): self
    {
        return new self(
            $inquiry->id,
            [$inquiry->couple_user_id, $inquiry->vendorProfile->user_id],
            [
                'id' => $message->id,
                'body' => $message->body,
                'sender' => [
                    'id' => $message->sender->id,
                    'name' => $message->sender->name,
                ],
                'created_at' => $message->created_at?->toISOString(),
            ],
        );
    }

    /** @return list<PrivateChannel> */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel("chat.inquiry.{$this->inquiryId}")];

        foreach ($this->participantIds as $participantId) {
            $channels[] = new PrivateChannel("chat.user.{$participantId}");
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    /** @return array{inquiry_id: int, message: array<string, mixed>} */
    public function broadcastWith(): array
    {
        return [
            'inquiry_id' => $this->inquiryId,
            'message' => $this->message,
        ];
    }
}
