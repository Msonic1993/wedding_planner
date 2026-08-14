<?php

namespace App\Domain\Profile\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'first_name',
    'last_name',
    'wedding_date',
    'budget',
    'guest_count',
    'location',
    'photo_path',
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
        ];
    }
}
