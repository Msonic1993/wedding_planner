<?php

namespace App\Application\Favorite\Services;

use App\Application\Favorite\Contracts\FavoriteService;
use App\Domain\Favorite\Models\VendorFavorite;
use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentFavoriteService implements FavoriteService
{
    public function list(User $user, int $perPage): LengthAwarePaginator
    {
        return VendorFavorite::query()
            ->where('user_id', $user->id)
            ->with(['vendorProfile' => fn ($query) => $query->withAvg('reviews', 'rating')->withCount('reviews')])
            ->latest()
            ->paginate($perPage);
    }

    public function save(User $user, VendorProfile $vendor): VendorFavorite
    {
        return VendorFavorite::query()->firstOrCreate([
            'user_id' => $user->id,
            'vendor_profile_id' => $vendor->id,
        ])->load(['vendorProfile' => fn ($query) => $query->withAvg('reviews', 'rating')->withCount('reviews')]);
    }

    public function remove(User $user, VendorProfile $vendor): void
    {
        VendorFavorite::query()
            ->where('user_id', $user->id)
            ->where('vendor_profile_id', $vendor->id)
            ->delete();
    }
}
