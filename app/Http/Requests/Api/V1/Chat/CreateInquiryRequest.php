<?php

namespace App\Http\Requests\Api\V1\Chat;

use App\Http\Requests\Api\V1\ApiRequest;

class CreateInquiryRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
            'requested_date' => ['nullable', 'date'],
        ];
    }
}
