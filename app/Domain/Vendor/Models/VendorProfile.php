<?php

namespace App\Domain\Vendor\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $website
 * @property string|null $voivodeship
 * @property string|null $country_code
 * @property string|null $region_code
 * @property array<int, string> $cities
 * @property array<int, int> $city_ids
 * @property array<int, string> $categories
 * @property string|null $price_from
 * @property string|null $price_to
 * @property Carbon|null $available_from
 * @property Carbon|null $available_to
 * @property array<int, string> $image_paths
 * @property numeric-string|null $reviews_avg_rating
 * @property int|null $reviews_count
 */
#[Fillable(['name', 'description', 'phone', 'email', 'website', 'social_media', 'country_code', 'region_code', 'voivodeship', 'cities', 'city_ids', 'categories', 'price_from', 'price_to', 'available_from', 'available_to', 'image_paths', 'price_list_path'])]
class VendorProfile extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<VendorReview, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(VendorReview::class);
    }

    protected function casts(): array
    {
        return ['social_media' => 'array', 'cities' => 'array', 'city_ids' => 'array', 'categories' => 'array', 'image_paths' => 'array', 'price_from' => 'decimal:2', 'price_to' => 'decimal:2', 'available_from' => 'date', 'available_to' => 'date'];
    }
}
