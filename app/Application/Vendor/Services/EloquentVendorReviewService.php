<?php

namespace App\Application\Vendor\Services;

use App\Application\Vendor\Contracts\VendorReviewService;
use App\Domain\Vendor\Exceptions\VendorReviewAlreadyExistsException;
use App\Domain\Vendor\Models\VendorProfile;
use App\Domain\Vendor\Models\VendorReview;
use App\Models\User;

class EloquentVendorReviewService implements VendorReviewService
{
    public function create(User $user, VendorProfile $vendor, array $attributes): VendorReview
    {
        if ($vendor->reviews()->where('user_id', $user->id)->exists()) {
            throw new VendorReviewAlreadyExistsException;
        }

        $review = new VendorReview($attributes);
        $review->user()->associate($user);
        $review->vendorProfile()->associate($vendor);
        $review->save();

        return $review;
    }

    public function update(User $user, VendorProfile $vendor, array $attributes): VendorReview
    {
        $review = $this->findOwnReview($user, $vendor);
        $review->update($attributes);

        return $review->refresh();
    }

    public function delete(User $user, VendorProfile $vendor): void
    {
        $this->findOwnReview($user, $vendor)->delete();
    }

    private function findOwnReview(User $user, VendorProfile $vendor): VendorReview
    {
        return $vendor->reviews()->where('user_id', $user->id)->firstOrFail();
    }
}
