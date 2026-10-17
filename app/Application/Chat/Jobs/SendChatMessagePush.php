<?php

namespace App\Application\Chat\Jobs;

use App\Application\Notification\Contracts\PushNotifier;
use App\Application\Notification\Data\PushNotification;
use App\Domain\Notification\Models\PushDevice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

/**
 * Notifies the offline participant about a new message. Runs on the default queue,
 * so slow Expo responses never delay WebSocket broadcasts.
 */
final class SendChatMessagePush implements ShouldQueue
{
    use Queueable;

    private const int PREVIEW_LENGTH = 120;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(
        public readonly int $recipientId,
        public readonly int $inquiryId,
        public readonly int $messageId,
        public readonly string $senderName,
        public readonly string $body,
        public readonly int $unreadTotal,
    ) {
        $this->afterCommit();
    }

    public function handle(PushNotifier $notifier): void
    {
        /** @var list<string> $tokens */
        $tokens = PushDevice::query()->where('user_id', $this->recipientId)->pluck('token')->all();

        if ($tokens === []) {
            return;
        }

        $unregistered = $notifier->send($tokens, new PushNotification(
            title: $this->senderName,
            body: Str::limit(Str::squish($this->body), self::PREVIEW_LENGTH),
            data: [
                'type' => 'chat.message',
                'inquiry_id' => $this->inquiryId,
                'message_id' => $this->messageId,
            ],
            badge: $this->unreadTotal,
        ));

        if ($unregistered !== []) {
            PushDevice::query()->whereIn('token', $unregistered)->delete();
        }
    }
}
