<?php

namespace App\Application\Chat\Services;

use App\Application\Chat\Contracts\InquiryChatService;
use App\Domain\Chat\Exceptions\InquiryAlreadyExistsException;
use App\Domain\Chat\Models\ChatMessage;
use App\Domain\Chat\Models\VendorInquiry;
use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EloquentInquiryChatService implements InquiryChatService
{
    public function createInquiry(User $couple, VendorProfile $vendor, array $attributes): VendorInquiry
    {
        if (VendorInquiry::query()->where('vendor_profile_id', $vendor->id)->where('couple_user_id', $couple->id)->exists()) {
            throw new InquiryAlreadyExistsException;
        }

        return DB::transaction(function () use ($attributes, $couple, $vendor): VendorInquiry {
            $inquiry = new VendorInquiry(['requested_date' => $attributes['requested_date'] ?? null]);
            $inquiry->vendorProfile()->associate($vendor);
            $inquiry->couple()->associate($couple);
            $inquiry->save();

            $message = new ChatMessage(['body' => $attributes['body']]);
            $message->inquiry()->associate($inquiry);
            $message->sender()->associate($couple);
            $message->save();

            return $inquiry->load(['vendorProfile', 'couple', 'messages' => fn ($query) => $query->latest()->limit(1)]);
        });
    }

    public function listInquiries(User $user, int $perPage): LengthAwarePaginator
    {
        $query = VendorInquiry::query()->with(['vendorProfile', 'couple'])->withCount('messages')->latest();

        if ($user->hasRole('vendor')) {
            $query->whereHas('vendorProfile', fn ($vendorQuery) => $vendorQuery->where('user_id', $user->id));
        } else {
            $query->where('couple_user_id', $user->id);
        }

        return $query->paginate($perPage);
    }

    public function getInquiry(User $user, VendorInquiry $inquiry): VendorInquiry
    {
        $this->ensureParticipant($user, $inquiry);

        return $inquiry->load(['vendorProfile', 'couple'])->loadCount('messages');
    }

    public function listMessages(User $user, VendorInquiry $inquiry, int $perPage): LengthAwarePaginator
    {
        $this->ensureParticipant($user, $inquiry);

        return $inquiry->messages()->with('sender')->orderByDesc('id')->paginate($perPage);
    }

    public function sendMessage(User $user, VendorInquiry $inquiry, array $attributes): ChatMessage
    {
        $this->ensureParticipant($user, $inquiry);

        $message = new ChatMessage($attributes);
        $message->inquiry()->associate($inquiry);
        $message->sender()->associate($user);
        $message->save();
        $inquiry->touch();

        return $message->load('sender');
    }

    private function ensureParticipant(User $user, VendorInquiry $inquiry): void
    {
        $isCouple = $inquiry->couple_user_id === $user->id;
        $isVendor = $inquiry->vendorProfile()->where('user_id', $user->id)->exists();

        if (! $isCouple && ! $isVendor) {
            throw new AuthorizationException;
        }
    }
}
