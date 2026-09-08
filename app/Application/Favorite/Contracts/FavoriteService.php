<?php

namespace App\Application\Favorite\Contracts;

use App\Domain\Favorite\Models\VendorFavorite;
use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface FavoriteService
{
    /** @return LengthAwarePaginator<int, VendorFavorite> */
    public function list(User $user, int $perPage): LengthAwarePaginator;

    public function save(User $user, VendorProfile $vendor): VendorFavorite;

    public function remove(User $user, VendorProfile $vendor): void;
}
