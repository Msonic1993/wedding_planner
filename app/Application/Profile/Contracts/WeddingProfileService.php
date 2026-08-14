<?php

namespace App\Application\Profile\Contracts;

use App\Domain\Profile\Models\WeddingProfile;
use App\Models\User;

interface WeddingProfileService
{
    /** @param array<string, mixed> $data */
    public function create(User $user, array $data): WeddingProfile;

    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): WeddingProfile;
}
