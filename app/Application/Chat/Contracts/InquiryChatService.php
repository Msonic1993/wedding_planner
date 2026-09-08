<?php

namespace App\Application\Chat\Contracts;

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

    /** @return LengthAwarePaginator<int, ChatMessage> */
    public function listMessages(User $user, VendorInquiry $inquiry, int $perPage): LengthAwarePaginator;

    /** @param array{body: string} $attributes */
    public function sendMessage(User $user, VendorInquiry $inquiry, array $attributes): ChatMessage;
}
