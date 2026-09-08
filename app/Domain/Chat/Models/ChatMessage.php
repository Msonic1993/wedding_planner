<?php

namespace App\Domain\Chat\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['body'])]
class ChatMessage extends Model
{
    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(VendorInquiry::class, 'vendor_inquiry_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
