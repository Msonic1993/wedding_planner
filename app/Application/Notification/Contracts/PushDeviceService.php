<?php

namespace App\Application\Notification\Contracts;

use App\Domain\Notification\Models\PushDevice;
use App\Models\User;

interface PushDeviceService
{
    /** Idempotent: an existing token is moved to $user and its platform is refreshed. */
    public function register(User $user, string $token, string $platform): PushDevice;

    /** @return bool false when $user has no device with this token */
    public function unregister(User $user, string $token): bool;
}
