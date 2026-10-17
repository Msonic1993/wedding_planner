<?php

namespace App\Application\Notification\Services;

use App\Application\Notification\Contracts\PushDeviceService;
use App\Domain\Notification\Models\PushDevice;
use App\Models\User;

class EloquentPushDeviceService implements PushDeviceService
{
    public function register(User $user, string $token, string $platform): PushDevice
    {
        // A phone can switch accounts; the token must then notify only the new account.
        $device = PushDevice::query()->firstOrNew(['token' => $token]);
        $device->platform = $platform;
        $device->user()->associate($user);
        $device->save();

        return $device;
    }

    public function unregister(User $user, string $token): bool
    {
        return PushDevice::query()->where('user_id', $user->id)->where('token', $token)->delete() > 0;
    }
}
