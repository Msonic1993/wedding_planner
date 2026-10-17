<?php

namespace App\Application\Chat\Contracts;

use App\Application\Chat\Data\ChatMessageWindow;
use App\Application\Chat\Data\ChatReadState;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\VendorInquiry;
use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InquiryChatService
{
    /** @param array{body: string, requested_date?: string|null} $attributes */
    public function createInquiry(User $couple, VendorProfile $vendor, array $attributes): VendorInquiry;

    /** @return LengthAwarePaginator<int, VendorInquiry> */
    public function listInquiries(User $user, int $perPage): LengthAwarePaginator;

    public function getInquiry(User $user, VendorInquiry $inquiry): VendorInquiry;

    public function isParticipant(User $user, VendorInquiry $inquiry): bool;

    /** @return LengthAwarePaginator<int, ChatMessage> */
    public function listMessages(User $user, VendorInquiry $inquiry, int $perPage): LengthAwarePaginator;

    /** Keyset window: messages older than $beforeId, or newer than $afterId when $beforeId is null. */
    public function listMessagesWindow(User $user, VendorInquiry $inquiry, ?int $beforeId, ?int $afterId, int $perPage): ChatMessageWindow;

    /** @param array{body: string} $attributes */
    public function sendMessage(User $user, VendorInquiry $inquiry, array $attributes): ChatMessage;

    /** Moves the user's read marker forward to $lastReadMessageId; older markers are ignored. */
    public function markAsRead(User $user, VendorInquiry $inquiry, int $lastReadMessageId): ChatReadState;

    /** Sum of unread messages across all threads of the user. */
    public function unreadTotal(User $user): int;
}
