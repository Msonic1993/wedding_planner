<?php

namespace App\Domain\Chat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $body
 * @property User $sender
 * @property Carbon|null $created_at
 */
#[Fillable(['body'])]
class ChatMessage extends Model
{
    /** @return BelongsTo<VendorInquiry, $this> */
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(VendorInquiry::class, 'vendor_inquiry_id');
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
