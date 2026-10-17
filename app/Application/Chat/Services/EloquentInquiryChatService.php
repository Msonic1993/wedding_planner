<?php

namespace App\Application\Chat\Services;

use App\Application\Chat\Contracts\InquiryChatService;
use App\Application\Chat\Data\ChatMessageWindow;
use App\Application\Chat\Data\ChatReadState;
use App\Application\Chat\Events\ChatMessageSent;
use App\Application\Chat\Events\ChatUnreadUpdated;
use App\Application\Chat\Jobs\SendChatMessagePush;
use App\Domain\Chat\Exceptions\InquiryAlreadyExistsException;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\VendorInquiry;
use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class EloquentInquiryChatService implements InquiryChatService
{
    public function __construct(
        private readonly Dispatcher $events,
        private readonly BusDispatcher $bus,
    ) {}

    public function createInquiry(User $couple, VendorProfile $vendor, array $attributes): VendorInquiry
    {
        if (VendorInquiry::query()->where('vendor_profile_id', $vendor->id)->where('couple_user_id', $couple->id)->exists()) {
            throw new InquiryAlreadyExistsException;
        }

        try {
            [$inquiry, $message] = DB::transaction(function () use ($attributes, $couple, $vendor): array {
                $inquiry = new VendorInquiry(['requested_date' => $attributes['requested_date'] ?? null]);
                $inquiry->vendorProfile()->associate($vendor);
                $inquiry->couple()->associate($couple);
                $inquiry->save();

                return [$inquiry, $this->appendMessage($inquiry, $couple, $attributes['body'])];
            });
        } catch (UniqueConstraintViolationException) {
            // A concurrent request created the same thread between the check and the insert.
            throw new InquiryAlreadyExistsException;
        }

        $inquiry->load(['vendorProfile', 'couple']);
        $this->notifyParticipants($inquiry, $message);

        return $inquiry;
    }

    public function listInquiries(User $user, int $perPage): LengthAwarePaginator
    {
        $query = VendorInquiry::query()
            ->with(['vendorProfile', 'couple'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($user->hasRole('vendor')) {
            $query->where('vendor_profile_id', $user->vendorProfile()->value('id') ?? 0);
        } else {
            $query->where('couple_user_id', $user->id);
        }

        return $query->paginate($perPage);
    }

    public function getInquiry(User $user, VendorInquiry $inquiry): VendorInquiry
    {
        $this->ensureParticipant($user, $inquiry);

        return $inquiry->load(['vendorProfile', 'couple']);
    }

    public function isParticipant(User $user, VendorInquiry $inquiry): bool
    {
        return $inquiry->couple_user_id === $user->id || $inquiry->vendorProfile->user_id === $user->id;
    }

    public function listMessages(User $user, VendorInquiry $inquiry, int $perPage): LengthAwarePaginator
    {
        $this->ensureParticipant($user, $inquiry);

        return $inquiry->messages()->with('sender')->orderByDesc('id')->paginate($perPage);
    }

    public function listMessagesWindow(User $user, VendorInquiry $inquiry, ?int $beforeId, ?int $afterId, int $perPage): ChatMessageWindow
    {
        $this->ensureParticipant($user, $inquiry);

        // Fetching one extra row tells whether more messages exist without COUNT(*).
        $query = $inquiry->messages()->with('sender')->limit($perPage + 1);

        if ($beforeId === null && $afterId !== null) {
            $messages = $query->where('id', '>', $afterId)->orderBy('id')->get();

            return new ChatMessageWindow($messages->take($perPage)->values(), $messages->count() > $perPage);
        }

        if ($beforeId !== null) {
            $query->where('id', '<', $beforeId);
        }

        $messages = $query->orderByDesc('id')->get();

        return new ChatMessageWindow($messages->take($perPage)->reverse()->values(), $messages->count() > $perPage);
    }

    public function sendMessage(User $user, VendorInquiry $inquiry, array $attributes): ChatMessage
    {
        $this->ensureParticipant($user, $inquiry);

        $message = DB::transaction(fn (): ChatMessage => $this->appendMessage($inquiry, $user, $attributes['body']));

        $this->notifyParticipants($inquiry, $message);

        return $message;
    }

    public function markAsRead(User $user, VendorInquiry $inquiry, int $lastReadMessageId): ChatReadState
    {
        $this->ensureParticipant($user, $inquiry);

        $side = $inquiry->participantSide($user->id);
        $markerColumn = "{$side}_last_read_message_id";
        $countColumn = "{$side}_unread_count";

        [$unreadCount, $changed] = DB::transaction(function () use ($inquiry, $user, $lastReadMessageId, $markerColumn, $countColumn): array {
            $current = VendorInquiry::query()->whereKey($inquiry->id)->lockForUpdate()->firstOrFail(['id', $markerColumn, $countColumn]);

            // Markers only move forward, so a late request from another device cannot bring badges back.
            if ($lastReadMessageId <= (int) $current->getAttribute($markerColumn)) {
                return [(int) $current->getAttribute($countColumn), false];
            }

            $unreadCount = $inquiry->messages()
                ->where('id', '>', $lastReadMessageId)
                ->where('sender_id', '!=', $user->id)
                ->count();

            $inquiry->forceFill([$markerColumn => $lastReadMessageId, $countColumn => $unreadCount])->save();

            return [$unreadCount, true];
        });

        $state = new ChatReadState($inquiry->id, $unreadCount, $this->unreadTotalFor($user->id, $side));

        if ($changed) {
            // Other devices of the same user refresh their badges; the calling device already has the response.
            $event = new ChatUnreadUpdated($user->id, $state->inquiryId, $state->unreadCount, $state->total);
            $event->dontBroadcastToCurrentUser();
            $this->events->dispatch($event);
        }

        return $state;
    }

    public function unreadTotal(User $user): int
    {
        return $this->unreadTotalFor($user->id, $user->hasRole('vendor') ? 'vendor' : 'couple');
    }

    private function appendMessage(VendorInquiry $inquiry, User $sender, string $body): ChatMessage
    {
        // The row lock serializes writers of one thread, so the counters and last message id
        // stay consistent under concurrent sends; other threads are not blocked.
        $current = VendorInquiry::query()->whereKey($inquiry->id)->lockForUpdate()
            ->firstOrFail(['id', 'messages_count', 'couple_unread_count', 'vendor_unread_count']);

        $message = new ChatMessage(['body' => $body]);
        $message->inquiry()->associate($inquiry);
        $message->sender()->associate($sender);
        $message->save();

        $senderSide = $inquiry->participantSide($sender->id);
        $recipientSide = $senderSide === 'couple' ? 'vendor' : 'couple';

        // Replying means the sender has seen the thread, so their marker moves to their own message.
        $inquiry->forceFill([
            'messages_count' => $current->messages_count + 1,
            'last_message_id' => $message->id,
            'last_message_at' => $message->created_at,
            "{$senderSide}_last_read_message_id" => $message->id,
            "{$senderSide}_unread_count" => 0,
            "{$recipientSide}_unread_count" => (int) $current->getAttribute("{$recipientSide}_unread_count") + 1,
        ])->save();

        return $message;
    }

    private function notifyParticipants(VendorInquiry $inquiry, ChatMessage $message): void
    {
        $event = ChatMessageSent::fromMessage($inquiry, $message);
        $event->dontBroadcastToCurrentUser();
        $this->events->dispatch($event);

        $recipientSide = $inquiry->participantSide($message->sender_id) === 'couple' ? 'vendor' : 'couple';
        $recipientId = $recipientSide === 'couple' ? $inquiry->couple_user_id : $inquiry->vendorProfile->user_id;
        $unreadCount = $inquiry->unreadCountFor($recipientId);
        $total = $this->unreadTotalFor($recipientId, $recipientSide);

        $this->events->dispatch(new ChatUnreadUpdated($recipientId, $inquiry->id, $unreadCount, $total));
        $this->bus->dispatch(new SendChatMessagePush(
            $recipientId,
            $inquiry->id,
            $message->id,
            $message->sender->name,
            $message->body,
            $total,
        ));
    }

    /** @param 'couple'|'vendor' $side */
    private function unreadTotalFor(int $userId, string $side): int
    {
        if ($side === 'couple') {
            return (int) VendorInquiry::query()->where('couple_user_id', $userId)->sum('couple_unread_count');
        }

        return (int) VendorInquiry::query()
            ->whereIn('vendor_profile_id', VendorProfile::query()->select('id')->where('user_id', $userId))
            ->sum('vendor_unread_count');
    }

    private function ensureParticipant(User $user, VendorInquiry $inquiry): void
    {
        if (! $this->isParticipant($user, $inquiry)) {
            throw new AuthorizationException;
        }
    }
}
