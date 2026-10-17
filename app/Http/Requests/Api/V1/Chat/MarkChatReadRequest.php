<?php

namespace App\Http\Requests\Api\V1\Chat;

use App\Domain\Chat\Models\VendorInquiry;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class MarkChatReadRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var VendorInquiry $inquiry */
        $inquiry = $this->route('inquiry');

        return [
            'last_read_message_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('chat_messages', 'id')->where('vendor_inquiry_id', $inquiry->id),
            ],
        ];
    }
}
