<?php

namespace Tests\Concerns;

use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;
use Spatie\Permission\Models\Role;

trait CreatesChatParticipants
{
    /** @return array{User, VendorProfile} */
    protected function vendor(): array
    {
        $vendor = $this->userWithRole('vendor');
        $profile = $vendor->vendorProfile()->create(['name' => 'Studio Foto', 'categories' => ['photographer']]);

        return [$vendor, $profile];
    }

    protected function userWithRole(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
