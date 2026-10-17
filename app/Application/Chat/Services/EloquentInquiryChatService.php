<?php

namespace App\Application\Chat\Services;

use App\Application\Chat\Contracts\InquiryChatService;
use App\Application\Chat\Data\ChatMessageWindow;
use App\Application\Chat\Events\ChatMessageSent;
use App\Domain\Chat\Exceptions\InquiryAlreadyExistsException;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\VendorInquiry;
use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class EloquentInquiryChatService implements InquiryChatService
{
    public function __construct(private readonly Dispatcher $events) {}

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

        $this->broadcastMessage($inquiry, $message);

        return $inquiry->load(['vendorProfile', 'couple']);
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

        $this->broadcastMessage($inquiry, $message);

        return $message;
    }

    private function appendMessage(VendorInquiry $inquiry, User $sender, string $body): ChatMessage
    {
        // The row lock serializes writers of one thread, so the counter and last message id
        // stay consistent under concurrent sends; other threads are not blocked.
        $current = VendorInquiry::query()->whereKey($inquiry->id)->lockForUpdate()->firstOrFail(['id', 'messages_count']);

        $message = new ChatMessage(['body' => $body]);
        $message->inquiry()->associate($inquiry);
        $message->sender()->associate($sender);
        $message->save();

        $inquiry->forceFill([
            'messages_count' => $current->messages_count + 1,
            'last_message_id' => $message->id,
            'last_message_at' => $message->created_at,
        ])->save();

        return $message;
    }

    private function broadcastMessage(VendorInquiry $inquiry, ChatMessage $message): void
    {
        $event = ChatMessageSent::fromMessage($inquiry, $message);
        $event->dontBroadcastToCurrentUser();

        $this->events->dispatch($event);
    }

    private function ensureParticipant(User $user, VendorInquiry $inquiry): void
    {
        if (! $this->isParticipant($user, $inquiry)) {
            throw new AuthorizationException;
        }
    }
}
