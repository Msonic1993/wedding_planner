<?php

namespace App\Domain\Chat\Models;

use App\Domain\Vendor\Models\VendorProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $couple_user_id
 * @property int $vendor_profile_id
 * @property Carbon|null $requested_date
 * @property int $messages_count
 * @property int|null $last_message_id
 * @property Carbon|null $last_message_at
 * @property VendorProfile $vendorProfile
 * @property User $couple
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['requested_date'])]
class VendorInquiry extends Model
{
    /** @return BelongsTo<VendorProfile, $this> */
    public function vendorProfile(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class);
    }

    /** @return BelongsTo<User, $this> */
    public function couple(): BelongsTo
    {
        return $this->belongsTo(User::class, 'couple_user_id');
    }

    /** @return HasMany<ChatMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    protected function casts(): array
    {
        return [
            'requested_date' => 'date',
            'messages_count' => 'integer',
            'last_message_id' => 'integer',
            'last_message_at' => 'datetime',
        ];
    }
}
