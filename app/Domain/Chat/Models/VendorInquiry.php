<?php

namespace App\Domain\Chat\Models;

use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['requested_date'])]
class VendorInquiry extends Model
{
    public function vendorProfile(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class);
    }

    public function couple(): BelongsTo
    {
        return $this->belongsTo(User::class, 'couple_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    protected function casts(): array
    {
        return ['requested_date' => 'date'];
    }
}
