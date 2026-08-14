<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiRequest;

class UpdateWeddingProfileRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:100'],
            'last_name' => ['sometimes', 'string', 'max:100'],
            'wedding_date' => ['nullable', 'date', 'after_or_equal:today'],
            'budget' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'location' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
