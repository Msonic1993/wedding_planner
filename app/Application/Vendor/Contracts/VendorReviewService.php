<?php

namespace App\Application\Vendor\Contracts;

use App\Domain\Vendor\Models\VendorProfile;
use App\Domain\Vendor\Models\VendorReview;
use App\Models\User;

interface VendorReviewService
{
    public function findOwn(User $user, VendorProfile $vendor): VendorReview;

    /** @param array{rating: int, comment?: string|null} $attributes */
    public function create(User $user, VendorProfile $vendor, array $attributes): VendorReview;

    /** @param array{rating: int, comment?: string|null} $attributes */
    public function update(User $user, VendorProfile $vendor, array $attributes): VendorReview;

    public function delete(User $user, VendorProfile $vendor): void;
}
