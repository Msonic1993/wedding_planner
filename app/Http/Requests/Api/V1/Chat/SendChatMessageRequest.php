<?php

namespace App\Http\Requests\Api\V1\Chat;

use App\Http\Requests\Api\V1\ApiRequest;

class SendChatMessageRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:5000']];
    }
}
