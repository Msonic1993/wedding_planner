<?php

namespace App\Domain\Vendor\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'phone', 'email', 'website', 'social_media', 'voivodeship', 'cities', 'categories', 'price_from', 'price_to', 'available_from', 'available_to', 'image_paths', 'price_list_path'])]
class VendorProfile extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VendorReview::class);
    }

    protected function casts(): array
    {
        return ['social_media' => 'array', 'cities' => 'array', 'categories' => 'array', 'image_paths' => 'array', 'price_from' => 'decimal:2', 'price_to' => 'decimal:2', 'available_from' => 'date', 'available_to' => 'date'];
    }
}
