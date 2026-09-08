<?php

namespace App\Http\Requests\Api\V1\Chat;

use App\Http\Requests\Api\V1\ApiRequest;

class ChatPaginationRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['per_page' => ['nullable', 'integer', 'min:1', 'max:100']];
    }
}
