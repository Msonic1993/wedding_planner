<?php

use App\Application\Chat\Contracts\InquiryChatService;
use App\Domain\Chat\Models\VendorInquiry;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.inquiry.{inquiry}', fn (User $user, VendorInquiry $inquiry): bool => app(InquiryChatService::class)->isParticipant($user, $inquiry));

Broadcast::channel('chat.user.{userId}', fn (User $user, int $userId): bool => $user->id === $userId);
