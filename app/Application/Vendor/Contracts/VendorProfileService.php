<?php

namespace App\Application\Vendor\Contracts;

use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;

interface VendorProfileService
{
    public function create(User $user, array $data): VendorProfile;

    public function update(User $user, array $data): VendorProfile;
}
