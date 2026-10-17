<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Domain\Profile\Enums\ProfileAvatar;
use App\Http\Requests\Api\V1\ApiRequest;
use App\Http\Requests\Api\V1\Concerns\ValidatesProfileLocation;
use Illuminate\Validation\Rule;

class UpdateWeddingProfileRequest extends ApiRequest
{
    use ValidatesProfileLocation;

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
            'country_code' => ['sometimes', 'nullable', 'required_with:region_code,city_id', 'string', 'size:2'],
            'region_code' => ['sometimes', 'nullable', 'required_with:country_code,city_id', 'string', 'max:20'],
            'city_id' => ['sometimes', 'nullable', 'required_with:country_code,region_code', 'integer'],
            'photo' => ['nullable', 'image', 'max:5120'],
            'avatar_key' => ['sometimes', 'nullable', 'string', Rule::in(ProfileAvatar::values())],
        ];
    }
}
