<?php

namespace App\Http\Requests\Api\V1\Marketplace;

use App\Http\Requests\Api\V1\ApiRequest;

class VendorReviewRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
