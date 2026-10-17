<?php

namespace App\Domain\Profile\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property Carbon|null $wedding_date
 * @property string|null $budget
 * @property int|null $guest_count
 * @property string|null $location
 * @property string|null $country_code
 * @property string|null $region_code
 * @property int|null $city_id
 * @property string|null $photo_path
 * @property string|null $avatar_key
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'first_name',
    'last_name',
    'wedding_date',
    'budget',
    'guest_count',
    'location',
    'country_code',
    'region_code',
    'city_id',
    'photo_path',
    'avatar_key',
])]
class WeddingProfile extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'wedding_date' => 'date',
            'budget' => 'decimal:2',
            'guest_count' => 'integer',
            'city_id' => 'integer',
        ];
    }
}
