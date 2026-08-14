<?php

namespace App\Domain\Vendor\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'description', 'phone', 'email', 'website', 'social_media', 'voivodeship', 'cities', 'categories', 'image_paths', 'price_list_path'])]
class VendorProfile extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['social_media' => 'array', 'cities' => 'array', 'categories' => 'array', 'image_paths' => 'array'];
    }
}
